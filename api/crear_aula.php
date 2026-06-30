<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_auth(['admin']);

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); echo json_encode(['message' => 'Método no permitido']); exit;
}

$body   = json_decode(file_get_contents('php://input'), true) ?? [];
$nombre = trim($body['nombre'] ?? '');

if (!$nombre) {
    http_response_code(400); echo json_encode(['message' => 'El nombre del aula es requerido']); exit;
}

$pdo   = getPDO();
$token = bin2hex(random_bytes(32)); // 64 chars hex, permanente

$stmt = $pdo->prepare('INSERT INTO aulas (nombre, token) VALUES (?, ?)');
$stmt->execute([$nombre, $token]);
$id = (int) $pdo->lastInsertId();

echo json_encode(['ok' => true, 'id' => $id, 'nombre' => $nombre, 'token' => $token]);
