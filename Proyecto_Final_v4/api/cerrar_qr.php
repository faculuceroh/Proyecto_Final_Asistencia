<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_auth(['profesor', 'admin']);

header('Content-Type: application/json; charset=utf-8');

$body     = json_decode(file_get_contents('php://input'), true) ?? [];
$clase_id = (int)($body['clase_id'] ?? 0);
$prof_id  = $_SESSION['usuario_id'];

if (!$clase_id) {
    http_response_code(400); echo json_encode(['message' => 'clase_id requerido']); exit;
}

$pdo = getPDO();

// Obtener sesión activa de esta clase
$stmt = $pdo->prepare(
    'SELECT id, tipo FROM qr_sesiones
     WHERE clase_id = ? AND activo = 1 ORDER BY id DESC LIMIT 1'
);
$stmt->execute([$clase_id]);
$sesion = $stmt->fetch();

$finalizada = false;

if ($sesion) {
    // Cerrar la sesión
    $pdo->prepare('UPDATE qr_sesiones SET activo = 0 WHERE id = ?')->execute([$sesion['id']]);

    // Si era salida → finalizar la clase
    if ($sesion['tipo'] === 'salida') {
        $pdo->prepare('UPDATE clases SET estado = "finalizada" WHERE id = ?')->execute([$clase_id]);
        $finalizada = true;
    }
}

echo json_encode(['ok' => true, 'finalizada' => $finalizada]);
