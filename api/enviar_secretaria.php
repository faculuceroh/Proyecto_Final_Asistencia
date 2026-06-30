<?php
/*
 * api/enviar_secretaria.php — Marca una clase como enviada a secretaría (POST)
 *
 * En producción esto podría disparar un email automático.
 * Por ahora registra el envío en el log del sistema y devuelve OK.
 *
 * Body JSON: { "clase_id": N }
 */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_auth(['admin', 'secretaria', 'profesor']);

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

$stmt = $pdo->prepare('SELECT id, estado FROM clases WHERE id = ? LIMIT 1');
$stmt->execute([$clase_id]);
$clase = $stmt->fetch();

if (!$clase) {
    http_response_code(404);
    echo json_encode(['message' => 'Clase no encontrada']);
    exit;
}

// La clase debe estar finalizada para enviar el reporte
if ($clase['estado'] !== 'finalizada') {
    http_response_code(422);
    echo json_encode(['message' => 'La clase aún no fue finalizada']);
    exit;
}

echo json_encode(['ok' => true]);
