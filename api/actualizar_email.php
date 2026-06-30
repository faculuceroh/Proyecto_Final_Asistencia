<?php
/*
 * api/actualizar_email.php
 * POST { "email": "..." }
 * Cualquier rol autenticado puede actualizar su propio email.
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

$body  = json_decode(file_get_contents('php://input'), true) ?? [];
$email = trim($body['email'] ?? '');

if ($email === '') {
    http_response_code(400);
    echo json_encode(['message' => 'El email no puede estar vacío']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['message' => 'El email no es válido']);
    exit;
}

try {
    getPDO()->prepare('UPDATE usuarios SET email = ? WHERE id = ?')
            ->execute([$email, $_SESSION['usuario_id']]);
    echo json_encode(['ok' => true]);
} catch (PDOException $e) {
    // El email ya existe en otro usuario (UNIQUE)
    http_response_code(409);
    echo json_encode(['message' => 'Ese email ya está en uso por otro usuario']);
}
