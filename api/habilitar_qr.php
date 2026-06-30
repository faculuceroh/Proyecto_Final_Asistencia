<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_auth(['profesor', 'admin']);

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); echo json_encode(['message' => 'Método no permitido']); exit;
}

$body      = json_decode(file_get_contents('php://input'), true) ?? [];
$clase_id  = (int)($body['clase_id']  ?? 0);
$aula_id   = (int)($body['aula_id']   ?? 0);
$tipo      = in_array($body['tipo'] ?? '', ['entrada','salida']) ? $body['tipo'] : null;
$prof_id   = $_SESSION['usuario_id'];

if (!$clase_id || !$aula_id || !$tipo) {
    http_response_code(400); echo json_encode(['message' => 'clase_id, aula_id y tipo son requeridos']); exit;
}

$pdo = getPDO();

// Verificar que la clase pertenece a este profesor y no está finalizada
$stmt = $pdo->prepare(
    'SELECT c.id, c.estado FROM clases c
     JOIN materias m ON m.id = c.materia_id
     WHERE c.id = ? AND (m.profesor_id = ? OR m.profesor_2_id = ?) AND c.estado != "finalizada"
     LIMIT 1'
);
$stmt->execute([$clase_id, $prof_id, $prof_id]);
$clase = $stmt->fetch();

if (!$clase) {
    http_response_code(403);
    echo json_encode(['message' => 'Clase no encontrada, no te pertenece, o ya fue finalizada']);
    exit;
}

// Verificar que el aula existe y está activa
$stmt = $pdo->prepare('SELECT id, nombre FROM aulas WHERE id = ? AND activo = 1 LIMIT 1');
$stmt->execute([$aula_id]);
$aula = $stmt->fetch();
if (!$aula) {
    http_response_code(404); echo json_encode(['message' => 'Aula no encontrada']); exit;
}

if ($tipo === 'entrada') {
    // Verificar que el aula no esté siendo usada por otro profesor
    $stmt = $pdo->prepare(
        'SELECT qs.id, m.nombre AS materia FROM qr_sesiones qs
         JOIN clases c ON c.id = qs.clase_id
         JOIN materias m ON m.id = c.materia_id
         WHERE qs.aula_id = ? AND qs.activo = 1 LIMIT 1'
    );
    $stmt->execute([$aula_id]);
    $en_uso = $stmt->fetch();
    if ($en_uso) {
        http_response_code(409);
        echo json_encode(['message' => 'El aula "' . $aula['nombre'] . '" ya está siendo usada por otra clase: ' . $en_uso['materia']]);
        exit;
    }

    // Verificar que esta clase no tenga ya una sesión activa
    $stmt = $pdo->prepare('SELECT id FROM qr_sesiones WHERE clase_id = ? AND activo = 1 LIMIT 1');
    $stmt->execute([$clase_id]);
    if ($stmt->fetch()) {
        http_response_code(409);
        echo json_encode(['message' => 'Esta clase ya tiene un QR activo']);
        exit;
    }

    // Crear sesión de entrada (sin expiración)
    $stmt = $pdo->prepare(
        'INSERT INTO qr_sesiones (aula_id, clase_id, tipo, profesor_id, activo, expira_en)
         VALUES (?, ?, "entrada", ?, 1, NULL)'
    );
    $stmt->execute([$aula_id, $clase_id, $prof_id]);

    // Si la clase estaba pendiente, pasarla a en_curso
    if ($clase['estado'] === 'pendiente') {
        $pdo->prepare('UPDATE clases SET estado = "en_curso" WHERE id = ?')->execute([$clase_id]);
    }

    echo json_encode(['ok' => true, 'tipo' => 'entrada', 'aula' => $aula['nombre'], 'expira_en' => null]);

} else {
    // tipo = 'salida'
    // Verificar que existe sesión de entrada activa para ESTA clase en ESTE aula
    $stmt = $pdo->prepare(
        'SELECT id FROM qr_sesiones
         WHERE clase_id = ? AND aula_id = ? AND tipo = "entrada" AND activo = 1 LIMIT 1'
    );
    $stmt->execute([$clase_id, $aula_id]);
    $sesion_entrada = $stmt->fetch();

    if (!$sesion_entrada) {
        http_response_code(400);
        echo json_encode(['message' => 'Primero debés habilitar el QR de entrada']);
        exit;
    }

    // Cerrar la sesión de entrada
    $pdo->prepare('UPDATE qr_sesiones SET activo = 0 WHERE id = ?')->execute([$sesion_entrada['id']]);

    // Crear sesión de salida con expiración de 15 minutos
    $expira = date('Y-m-d H:i:s', time() + 15 * 60);
    $stmt = $pdo->prepare(
        'INSERT INTO qr_sesiones (aula_id, clase_id, tipo, profesor_id, activo, expira_en)
         VALUES (?, ?, "salida", ?, 1, ?)'
    );
    $stmt->execute([$aula_id, $clase_id, $prof_id, $expira]);

    echo json_encode(['ok' => true, 'tipo' => 'salida', 'aula' => $aula['nombre'], 'expira_en' => $expira]);
}
