<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_auth(['secretaria', 'admin']);

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
    echo json_encode(['message' => 'ID de clase inválido.']);
    exit;
}

$pdo = getPDO();

// Verificar que la clase existe
$stmt = $pdo->prepare("SELECT id FROM clases WHERE id = ? LIMIT 1");
$stmt->execute([$clase_id]);
if (!$stmt->fetch()) {
    http_response_code(404);
    echo json_encode(['message' => 'Clase no encontrada.']);
    exit;
}

// Borrar físicamente la clase (las dependencias asistencias y qr_tokens se borran por ON DELETE CASCADE)
$stmt = $pdo->prepare("DELETE FROM clases WHERE id = ?");
$stmt->execute([$clase_id]);

echo json_encode(['ok' => true]);
