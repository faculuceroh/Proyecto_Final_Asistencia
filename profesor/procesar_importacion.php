<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';
require_once '../includes/TeamsAttendanceParser.php';
require_once '../includes/AsistenciaVirtual.php';

require_auth(['profesor', 'admin']);

// Solo aceptar POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: importar_teams.php');
    exit;
}

// ── 1. Validar clase_id ───────────────────────────────────────────────────────
$clase_id = (int)($_POST['clase_id'] ?? 0);
if (!$clase_id) {
    header('Location: importar_teams.php?error=' . urlencode('Seleccioná una clase.'));
    exit;
}

// ── 2. Validar archivo subido ─────────────────────────────────────────────────
$archivo = $_FILES['archivo_teams'] ?? null;

if (!$archivo || $archivo['error'] !== UPLOAD_ERR_OK) {
    $codigos = [
        UPLOAD_ERR_INI_SIZE   => 'El archivo supera el límite del servidor.',
        UPLOAD_ERR_FORM_SIZE  => 'El archivo supera el límite del formulario.',
        UPLOAD_ERR_NO_FILE    => 'No se seleccionó ningún archivo.',
        UPLOAD_ERR_NO_TMP_DIR => 'No hay directorio temporal disponible.',
        UPLOAD_ERR_CANT_WRITE => 'No se pudo escribir el archivo en disco.',
    ];
    $msg = $codigos[$archivo['error'] ?? -1] ?? 'Error al subir el archivo.';
    header('Location: importar_teams.php?error=' . urlencode($msg));
    exit;
}

if ($archivo['size'] > 2 * 1024 * 1024) {
    header('Location: importar_teams.php?error=' . urlencode('El archivo supera los 2 MB permitidos.'));
    exit;
}

$extension = strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION));
if ($extension !== 'csv') {
    header('Location: importar_teams.php?error=' . urlencode('El archivo debe tener extensión .csv.'));
    exit;
}

// ── 3. Mover a directorio temporal ───────────────────────────────────────────
$uploadDir = __DIR__ . '/../uploads/teams_temp/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}
$tempPath = $uploadDir . uniqid('teams_', true) . '.csv';

if (!move_uploaded_file($archivo['tmp_name'], $tempPath)) {
    header('Location: importar_teams.php?error=' . urlencode('No se pudo guardar el archivo temporalmente.'));
    exit;
}

// ── 4. Parsear + procesar (el archivo se borra en el finally) ─────────────────
$error     = null;
$resultado = null;

try {
    $participantes = TeamsAttendanceParser::parse($tempPath);

    $pdo = getPDO();
    $av  = new AsistenciaVirtual($pdo);

    $resultado = $av->procesar($participantes, $clase_id);

    // Verificar que el profesor tiene permiso sobre esta clase
    $prof_id = $_SESSION['usuario_id'];
    $stmt = $pdo->prepare(
        'SELECT 1 FROM clases c
         JOIN materias m ON m.id = c.materia_id
         WHERE c.id = ? AND (m.profesor_id = ? OR m.profesor_2_id = ?)
         LIMIT 1'
    );
    $stmt->execute([$clase_id, $prof_id, $prof_id]);
    if (!$stmt->fetch() && $_SESSION['rol'] !== 'admin') {
        throw new RuntimeException('No tenés permiso para importar asistencia en esta clase.');
    }

    // Guardar en sesión para el paso de confirmación
    $_SESSION['teams_import_preview'] = [
        'clase_id'  => $clase_id,
        'resultado' => $resultado,
        'timestamp' => time(),
        'archivo'   => $archivo['name'],
    ];

} catch (RuntimeException $e) {
    $error = $e->getMessage();
} finally {
    // Borrar archivo temporal siempre, aunque falle el parser
    if (file_exists($tempPath)) {
        @unlink($tempPath);
    }
}

if ($error) {
    header('Location: importar_teams.php?error=' . urlencode($error));
    exit;
}

$partes    = explode(' ', $_SESSION['nombre']);
$iniciales = strtoupper(substr($partes[0], 0, 1) . substr($partes[1] ?? '', 0, 1));

require_once '../views/profesor/procesar_importacion_view.php';
