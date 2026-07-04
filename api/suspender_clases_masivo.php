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

$body   = json_decode(file_get_contents('php://input'), true) ?? [];
$fechas = (array) ($body['fechas'] ?? []);

// Filtrar vacíos y validar fechas
$fechas = array_filter(array_map('trim', $fechas));

if (empty($fechas)) {
    http_response_code(400);
    echo json_encode(['message' => 'Debe seleccionar al menos una fecha para suspender clases.']);
    exit;
}

$pdo = getPDO();

try {
    $placeholders = implode(',', array_fill(0, count($fechas), '?'));
    $stmt = $pdo->prepare("
        UPDATE clases
        SET estado = 'suspendida'
        WHERE fecha IN ($placeholders) AND estado IN ('pendiente', 'en_curso')
    ");
    $stmt->execute(array_values($fechas));
    $afectadas = $stmt->rowCount();

    echo json_encode([
        'ok' => true,
        'afectadas' => $afectadas
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['message' => 'Error al suspender las clases en la base de datos.']);
}
