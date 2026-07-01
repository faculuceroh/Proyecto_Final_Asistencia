<?php
/*
 * api/confirmar_importacion.php  — POST (sin body)
 *
 * Lee el preview de importación guardado en sesión por procesar_importacion.php
 * y graba/actualiza los registros en `asistencias` usando
 * INSERT ... ON DUPLICATE KEY UPDATE (la UNIQUE KEY uq_asistencia ya existe).
 *
 * Inserta tanto los `importados` (match limpio) como las `advertencias`
 * (match por nombre o no inscripto) — el profesor los revisó y confirmó ambos.
 * Los `no_matcheados` no se insertan.
 */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_auth(['profesor', 'admin']);

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['message' => 'Método no permitido']);
    exit;
}

// ── 1. Leer preview de sesión ─────────────────────────────────────────────────
$preview = $_SESSION['teams_import_preview'] ?? null;

if (!$preview) {
    http_response_code(400);
    echo json_encode(['message' => 'No hay una importación pendiente de confirmar. Volvé a subir el archivo.']);
    exit;
}

// Expirar si tiene más de 30 minutos (el profesor dejó la página abierta)
if (time() - ($preview['timestamp'] ?? 0) > 1800) {
    unset($_SESSION['teams_import_preview']);
    http_response_code(400);
    echo json_encode(['message' => 'La sesión de importación expiró (30 min). Volvé a subir el archivo.']);
    exit;
}

$clase_id  = (int)$preview['clase_id'];
$resultado = $preview['resultado'];

// ── 2. Verificar permiso del profesor sobre la clase ─────────────────────────
if ($_SESSION['rol'] !== 'admin') {
    $prof_id = $_SESSION['usuario_id'];
    $stmt = getPDO()->prepare(
        'SELECT 1 FROM clases c
         JOIN materias m ON m.id = c.materia_id
         WHERE c.id = ? AND (m.profesor_id = ? OR m.profesor_2_id = ?)
         LIMIT 1'
    );
    $stmt->execute([$clase_id, $prof_id, $prof_id]);
    if (!$stmt->fetch()) {
        http_response_code(403);
        echo json_encode(['message' => 'No tenés permiso para confirmar esta importación.']);
        exit;
    }
}

// ── 3. Preparar los registros a insertar ─────────────────────────────────────
//   Combinar importados + advertencias; excluir los sin alumno_id (no_matcheados)
$registros = array_merge(
    $resultado['importados']   ?? [],
    $resultado['advertencias'] ?? []
);

if (empty($registros)) {
    unset($_SESSION['teams_import_preview']);
    echo json_encode(['ok' => true, 'insertados' => 0, 'actualizados' => 0,
                      'message' => 'No había registros para importar.']);
    exit;
}

// ── 4. INSERT ... ON DUPLICATE KEY UPDATE ────────────────────────────────────
$pdo  = getPDO();
$stmt = $pdo->prepare(
    'INSERT INTO asistencias (alumno_id, clase_id, hora_entrada, hora_salida, estado)
     VALUES (?, ?, ?, ?, ?)
     ON DUPLICATE KEY UPDATE
       hora_entrada = VALUES(hora_entrada),
       hora_salida  = VALUES(hora_salida),
       estado       = VALUES(estado)'
);

$insertados   = 0;
$actualizados = 0;
$errores      = [];

$pdo->beginTransaction();
try {
    foreach ($registros as $r) {
        $alumno_id   = (int)$r['alumno_id'];
        $hora_entrada = $r['hora_entrada'] ?: null;
        $hora_salida  = $r['hora_salida']  ?: null;
        $estado       = $r['estado'];

        // Verificar si ya existía el registro para contabilizar correctamente
        $existe = $pdo->prepare(
            'SELECT id FROM asistencias WHERE alumno_id = ? AND clase_id = ? LIMIT 1'
        );
        $existe->execute([$alumno_id, $clase_id]);
        $yaExistia = (bool)$existe->fetch();

        $stmt->execute([$alumno_id, $clase_id, $hora_entrada, $hora_salida, $estado]);

        if ($yaExistia) {
            $actualizados++;
        } else {
            $insertados++;
        }
    }
    $pdo->commit();
} catch (PDOException $e) {
    $pdo->rollBack();
    error_log('confirmar_importacion: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['message' => 'Error al grabar la asistencia. Intentá de nuevo.']);
    exit;
}

// ── 5. Marcar la clase como finalizada ───────────────────────────────────────
//   Una clase virtual con asistencia importada se considera finalizada.
//   Esto permite que aparezca en el historial y en los contadores de stats.
$pdo->prepare("UPDATE clases SET estado = 'finalizada' WHERE id = ? AND estado != 'finalizada'")
    ->execute([$clase_id]);

// ── 6. Limpiar sesión ─────────────────────────────────────────────────────────
unset($_SESSION['teams_import_preview']);

echo json_encode([
    'ok'          => true,
    'insertados'  => $insertados,
    'actualizados'=> $actualizados,
    'total'       => $insertados + $actualizados,
]);
