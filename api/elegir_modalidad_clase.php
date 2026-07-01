<?php
/*
 * api/elegir_modalidad_clase.php
 * POST { clase_id, modalidad: 'presencial'|'virtual' }
 *
 * Se usa al "dar la clase": si la materia es de modalidad "híbrida", la clase
 * arranca con modalidad='hibrida' y el profesor recién decide acá, en el momento,
 * si la dicta presencial o virtual. Una vez elegida, la clase queda fijada a esa
 * modalidad (no se puede volver a "hibrida").
 */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_auth(['profesor', 'admin']);

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['message' => 'Método no permitido']);
    exit;
}

$body      = json_decode(file_get_contents('php://input'), true) ?? [];
$clase_id  = (int) ($body['clase_id']  ?? 0);
$modalidad = $body['modalidad'] ?? '';

if (!$clase_id || !in_array($modalidad, ['presencial', 'virtual'], true)) {
    http_response_code(400);
    echo json_encode(['message' => 'Datos inválidos']);
    exit;
}

$pdo = getPDO();

// La clase tiene que pertenecer a una materia del profesor logueado, seguir
// en estado "hibrida" (todavía sin decidir) y no estar finalizada.
$stmt = $pdo->prepare(
    'SELECT c.id, c.modalidad, c.estado
     FROM clases c
     JOIN materias m ON m.id = c.materia_id
     WHERE c.id = ? AND (m.profesor_id = ? OR m.profesor_2_id = ?)
     LIMIT 1'
);
$stmt->execute([$clase_id, $_SESSION['usuario_id'], $_SESSION['usuario_id']]);
$clase = $stmt->fetch();

if (!$clase) {
    http_response_code(404);
    echo json_encode(['message' => 'Clase no encontrada']);
    exit;
}

if ($clase['modalidad'] !== 'hibrida') {
    http_response_code(409);
    echo json_encode(['message' => 'Esta clase ya tiene una modalidad definida']);
    exit;
}

if ($clase['estado'] === 'finalizada') {
    http_response_code(409);
    echo json_encode(['message' => 'La clase ya finalizó']);
    exit;
}

$upd = $pdo->prepare('UPDATE clases SET modalidad = ? WHERE id = ?');
$upd->execute([$modalidad, $clase_id]);

echo json_encode(['ok' => true, 'modalidad' => $modalidad]);
