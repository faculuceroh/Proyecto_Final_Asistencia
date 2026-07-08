<?php
/*
 * api/eliminar_usuario.php — Borra un usuario definitivamente (POST)
 * Body JSON: { id }
 *
 * inscripciones y asistencias del usuario cascadean por FK; materias.profesor_id
 * queda en NULL por FK. materias.profesor_2_id no tiene FK propia y se limpia a mano.
 */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_auth(['admin', 'secretaria']);

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['message' => 'Método no permitido']);
    exit;
}

$body = json_decode(file_get_contents('php://input'), true) ?? [];
$id   = (int) ($body['id'] ?? 0);

if (!$id) {
    http_response_code(400);
    echo json_encode(['message' => 'ID de usuario inválido']);
    exit;
}

// No permitir borrar la propia cuenta
if ($id === $_SESSION['usuario_id']) {
    http_response_code(403);
    echo json_encode(['message' => 'No podés borrar tu propia cuenta']);
    exit;
}

$pdo = getPDO();

$stmt = $pdo->prepare('SELECT id FROM usuarios WHERE id = ? LIMIT 1');
$stmt->execute([$id]);
if (!$stmt->fetch()) {
    http_response_code(404);
    echo json_encode(['message' => 'Usuario no encontrado']);
    exit;
}

try {
    $pdo->beginTransaction();
    $pdo->prepare('UPDATE materias SET profesor_2_id = NULL WHERE profesor_2_id = ?')->execute([$id]);
    $pdo->prepare('DELETE FROM usuarios WHERE id = ?')->execute([$id]);
    $pdo->commit();
} catch (PDOException $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['message' => 'Error de base de datos']);
    exit;
}

echo json_encode(['ok' => true]);
