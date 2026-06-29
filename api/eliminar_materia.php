<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_auth(['secretaria', 'admin']);

header('Content-Type: application/json');

$body       = json_decode(file_get_contents('php://input'), true);
$materia_id = (int)($body['materia_id'] ?? 0);

if (!$materia_id) {
    http_response_code(400);
    echo json_encode(['error' => 'ID de materia inválido.']);
    exit;
}

$pdo = getPDO();

// Verificar que existe
$stmt = $pdo->prepare("SELECT id, nombre FROM materias WHERE id = ? AND activo = 1 LIMIT 1");
$stmt->execute([$materia_id]);
if (!$stmt->fetch()) {
    http_response_code(404);
    echo json_encode(['error' => 'Materia no encontrada.']);
    exit;
}

// Baja lógica: desactiva la materia y limpia horarios e inscripciones
$pdo->prepare("UPDATE materias SET activo = 0 WHERE id = ?")->execute([$materia_id]);
$pdo->prepare("DELETE FROM materia_horarios WHERE materia_id = ?")->execute([$materia_id]);
$pdo->prepare("DELETE FROM inscripciones WHERE materia_id = ?")->execute([$materia_id]);

echo json_encode(['ok' => true]);
