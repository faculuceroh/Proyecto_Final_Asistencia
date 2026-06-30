<?php
/*
 * api/finalizar.php — Cierra la clase y marca ausentes (POST)
 * Llamado por el botón "Finalizar asistencia" en qr-display.js.
 *
 * Body JSON:
 *   { "clase_id": N }
 *
 * Respuesta:
 *   { "ok": true, "ausentes": N }
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

$body     = json_decode(file_get_contents('php://input'), true) ?? [];
$clase_id = (int) ($body['clase_id'] ?? 0);

if (!$clase_id) {
    http_response_code(400);
    echo json_encode(['message' => 'clase_id requerido']);
    exit;
}

$pdo = getPDO();

// Verifica que la clase exista y esté en curso
$stmt = $pdo->prepare('SELECT estado, materia_id FROM clases WHERE id = ? LIMIT 1');
$stmt->execute([$clase_id]);
$clase = $stmt->fetch();

if (!$clase) {
    http_response_code(404);
    echo json_encode(['message' => 'Clase no encontrada']);
    exit;
}
if ($clase['estado'] === 'finalizada') {
    http_response_code(409);
    echo json_encode(['message' => 'La clase ya estaba finalizada']);
    exit;
}

// Inserta "ausente" para todos los alumnos inscriptos que no escanearon
$pdo->prepare(
    'INSERT IGNORE INTO asistencias (alumno_id, clase_id, estado)
     SELECT i.alumno_id, ?, "ausente"
     FROM inscripciones i
     WHERE i.materia_id = ?
       AND i.alumno_id NOT IN (
           SELECT alumno_id FROM asistencias WHERE clase_id = ?
       )'
)->execute([$clase_id, $clase['materia_id'], $clase_id]);

$ausentes = $pdo->prepare(
    'SELECT COUNT(*) FROM asistencias WHERE clase_id = ? AND estado = "ausente"'
);
$ausentes->execute([$clase_id]);
$total_ausentes = (int) $ausentes->fetchColumn();

// Desactiva todos los tokens de esta clase
$pdo->prepare('UPDATE qr_tokens SET activo = 0 WHERE clase_id = ?')
    ->execute([$clase_id]);

// Marca la clase como finalizada
$pdo->prepare('UPDATE clases SET estado = "finalizada" WHERE id = ?')
    ->execute([$clase_id]);

echo json_encode([
    'ok'      => true,
    'ausentes' => $total_ausentes,
]);
