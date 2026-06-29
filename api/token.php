<?php
/*
 * api/token.php — Genera el token rotativo del QR (GET)
 * Llamado por qr-display.js cada 30 segundos.
 *
 * Parámetros GET:
 *   clase_id  (int, requerido)
 *
 * Respuesta:
 *   { "token": "abc12345" }
 */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_auth(['profesor', 'admin']);

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['message' => 'Método no permitido']);
    exit;
}

$clase_id = (int) ($_GET['clase_id'] ?? 0);
if (!$clase_id) {
    http_response_code(400);
    echo json_encode(['message' => 'clase_id requerido']);
    exit;
}

$pdo = getPDO();

// Verifica que la clase exista y no esté finalizada
$stmt = $pdo->prepare('SELECT estado FROM clases WHERE id = ? LIMIT 1');
$stmt->execute([$clase_id]);
$clase = $stmt->fetch();

if (!$clase) {
    http_response_code(404);
    echo json_encode(['message' => 'Clase no encontrada']);
    exit;
}
if ($clase['estado'] === 'finalizada') {
    http_response_code(403);
    echo json_encode(['message' => 'La clase ya fue finalizada']);
    exit;
}

// Duración del token (segundos) desde configuración
$rotacion = (int) $pdo->query(
    "SELECT valor FROM configuracion WHERE clave = 'qr_rotacion_segundos'"
)->fetchColumn() ?: 30;

// Desactiva tokens anteriores de esta clase
$pdo->prepare('UPDATE qr_tokens SET activo = 0 WHERE clase_id = ?')
    ->execute([$clase_id]);

// Genera token aleatorio (8 chars hex = 4 bytes)
$token = bin2hex(random_bytes(4));

// Inserta el nuevo token
$pdo->prepare(
    'INSERT INTO qr_tokens (clase_id, token, tipo, expira_en)
     VALUES (?, ?, "entrada", DATE_ADD(NOW(), INTERVAL ? SECOND))'
)->execute([$clase_id, $token, $rotacion + 5]); // +5s de margen

// Si la clase estaba pendiente, la pone en curso
if ($clase['estado'] === 'pendiente') {
    $pdo->prepare('UPDATE clases SET estado = "en_curso" WHERE id = ?')
        ->execute([$clase_id]);
}

echo json_encode(['token' => $token]);
