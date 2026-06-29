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
$usuario_id = (int) ($body['usuario_id'] ?? 0);
$activo     = isset($body['activo']) ? (int) (bool) $body['activo'] : null;

if (!$usuario_id || $activo === null) {
    http_response_code(400);
    echo json_encode(['message' => 'Datos incompletos']);
    exit;
}

// No permitir desactivar la propia cuenta
if ($usuario_id === $_SESSION['usuario_id']) {
    http_response_code(403);
    echo json_encode(['message' => 'No podés desactivar tu propia cuenta']);
    exit;
}

try {
    $stmt = getPDO()->prepare('UPDATE usuarios SET activo = ? WHERE id = ?');
    $stmt->execute([$activo, $usuario_id]);

    if ($stmt->rowCount() === 0) {
        http_response_code(404);
        echo json_encode(['message' => 'Usuario no encontrado']);
        exit;
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['message' => 'Error de base de datos']);
    exit;
}

echo json_encode(['ok' => true]);
