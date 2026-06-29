<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_auth(['admin']);

header('Content-Type: application/json; charset=utf-8');

$body    = json_decode(file_get_contents('php://input'), true) ?? [];
$aula_id = (int)($body['aula_id'] ?? 0);

if (!$aula_id) {
    http_response_code(400); echo json_encode(['message' => 'aula_id requerido']); exit;
}

$pdo = getPDO();
$pdo->prepare('UPDATE aulas SET activo = 0 WHERE id = ?')->execute([$aula_id]);

echo json_encode(['ok' => true]);
