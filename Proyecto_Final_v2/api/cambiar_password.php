<?php
/*
 * api/cambiar_password.php
 * POST { "password_actual": "...", "password_nueva": "..." }
 * Cualquier rol autenticado puede cambiar su propia contraseña.
 */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_auth();

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['message' => 'Método no permitido']);
    exit;
}

$body    = json_decode(file_get_contents('php://input'), true) ?? [];
$actual  = $body['password_actual'] ?? '';
$nueva   = $body['password_nueva']  ?? '';

if (!$actual || !$nueva) {
    http_response_code(400);
    echo json_encode(['message' => 'Completá los dos campos']);
    exit;
}

if (strlen($nueva) < 6) {
    http_response_code(422);
    echo json_encode(['message' => 'La nueva contraseña debe tener al menos 6 caracteres']);
    exit;
}

$pdo  = getPDO();
$stmt = $pdo->prepare('SELECT password FROM usuarios WHERE id = ? LIMIT 1');
$stmt->execute([$_SESSION['usuario_id']]);
$hash = $stmt->fetchColumn();

if (!$hash || !password_verify($actual, $hash)) {
    http_response_code(401);
    echo json_encode(['message' => 'La contraseña actual es incorrecta']);
    exit;
}

$nuevo_hash = password_hash($nueva, PASSWORD_BCRYPT);
$pdo->prepare('UPDATE usuarios SET password = ? WHERE id = ?')
    ->execute([$nuevo_hash, $_SESSION['usuario_id']]);

echo json_encode(['ok' => true]);
