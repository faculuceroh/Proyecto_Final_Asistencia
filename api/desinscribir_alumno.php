<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_auth(['admin', 'secretaria']);

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['message' => 'Método no permitido']);
    exit;
}

$body       = json_decode(file_get_contents('php://input'), true) ?? [];
$materia_id = (int) ($body['materia_id'] ?? 0);
$legajo     = trim($body['legajo'] ?? '');

if (!$materia_id || !$legajo) {
    http_response_code(400);
    echo json_encode(['message' => 'materia_id y legajo son obligatorios']);
    exit;
}

$pdo  = getPDO();
$stmt = $pdo->prepare(
    "DELETE FROM inscripciones
     WHERE materia_id = ?
       AND alumno_id = (SELECT id FROM usuarios WHERE legajo = ? AND rol = 'alumno' LIMIT 1)"
);
$stmt->execute([$materia_id, $legajo]);

if ($stmt->rowCount() === 0) {
    http_response_code(404);
    echo json_encode(['message' => 'Inscripción no encontrada']);
    exit;
}

echo json_encode(['ok' => true]);
