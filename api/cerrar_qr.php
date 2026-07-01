<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_auth(['profesor', 'admin']);

header('Content-Type: application/json; charset=utf-8');

$body     = json_decode(file_get_contents('php://input'), true) ?? [];
$clase_id = (int)($body['clase_id'] ?? 0);
$prof_id  = $_SESSION['usuario_id'];

if (!$clase_id) {
    http_response_code(400); echo json_encode(['message' => 'clase_id requerido']); exit;
}

$pdo = getPDO();

// Verifica que la clase exista y no esté ya finalizada
$stmt = $pdo->prepare('SELECT estado, materia_id FROM clases WHERE id = ? LIMIT 1');
$stmt->execute([$clase_id]);
$clase = $stmt->fetch();

if (!$clase) {
    http_response_code(404); echo json_encode(['message' => 'Clase no encontrada']); exit;
}
if ($clase['estado'] === 'finalizada') {
    echo json_encode(['ok' => true, 'finalizada' => true, 'ausentes' => 0]);
    exit;
}

// Cerrar cualquier sesión de QR activa de esta clase (entrada o salida)
$pdo->prepare('UPDATE qr_sesiones SET activo = 0 WHERE clase_id = ? AND activo = 1')
    ->execute([$clase_id]);

// Marcar como ausentes a los inscriptos que no tengan registro de asistencia
$pdo->prepare(
    'INSERT IGNORE INTO asistencias (alumno_id, clase_id, estado)
     SELECT i.alumno_id, ?, "ausente"
     FROM inscripciones i
     WHERE i.materia_id = ?
       AND i.alumno_id NOT IN (SELECT alumno_id FROM asistencias WHERE clase_id = ?)'
)->execute([$clase_id, $clase['materia_id'], $clase_id]);

$stmt = $pdo->prepare('SELECT COUNT(*) FROM asistencias WHERE clase_id = ? AND estado = "ausente"');
$stmt->execute([$clase_id]);
$ausentes = (int) $stmt->fetchColumn();

$pdo->prepare('UPDATE clases SET estado = "finalizada" WHERE id = ?')->execute([$clase_id]);

echo json_encode(['ok' => true, 'finalizada' => true, 'ausentes' => $ausentes]);
