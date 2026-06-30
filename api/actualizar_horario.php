<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_auth(['secretaria', 'admin']);

header('Content-Type: application/json');

$body  = json_decode(file_get_contents('php://input'), true);
$accion = trim($body['accion'] ?? '');
$pdo   = getPDO();

// ── Actualizar ────────────────────────────────────────────────
if ($accion === 'actualizar') {
    $id          = (int)($body['id']         ?? 0);
    $dia_semana  = (int)($body['dia_semana'] ?? 0);
    $hora_inicio = trim($body['hora_inicio'] ?? '');
    $hora_fin    = trim($body['hora_fin']    ?? '');

    if (!$id || $dia_semana < 1 || $dia_semana > 7 || !$hora_inicio || !$hora_fin) {
        http_response_code(400);
        echo json_encode(['error' => 'Datos incompletos.']);
        exit;
    }

    $stmt = $pdo->prepare(
        "UPDATE materia_horarios
         SET dia_semana = ?, hora_inicio = ?, hora_fin = ?
         WHERE id = ?"
    );
    $stmt->execute([$dia_semana, $hora_inicio, $hora_fin, $id]);

    echo json_encode(['ok' => true]);
    exit;
}

// ── Eliminar ──────────────────────────────────────────────────
if ($accion === 'eliminar') {
    $id = (int)($body['id'] ?? 0);
    if (!$id) {
        http_response_code(400);
        echo json_encode(['error' => 'ID inválido.']);
        exit;
    }

    $pdo->prepare("DELETE FROM materia_horarios WHERE id = ?")->execute([$id]);
    echo json_encode(['ok' => true]);
    exit;
}

// ── Crear ─────────────────────────────────────────────────────
if ($accion === 'crear') {
    $materia_id  = (int)($body['materia_id']  ?? 0);
    $dia_semana  = (int)($body['dia_semana']  ?? 0);
    $hora_inicio = trim($body['hora_inicio']  ?? '');
    $hora_fin    = trim($body['hora_fin']     ?? '');

    if (!$materia_id || $dia_semana < 1 || $dia_semana > 7 || !$hora_inicio || !$hora_fin) {
        http_response_code(400);
        echo json_encode(['error' => 'Datos incompletos.']);
        exit;
    }

    // Verificar que la materia existe
    $stmt = $pdo->prepare("SELECT id FROM materias WHERE id = ? AND activo = 1 LIMIT 1");
    $stmt->execute([$materia_id]);
    if (!$stmt->fetch()) {
        http_response_code(404);
        echo json_encode(['error' => 'Materia no encontrada.']);
        exit;
    }

    $stmt = $pdo->prepare(
        "INSERT INTO materia_horarios (materia_id, dia_semana, hora_inicio, hora_fin)
         VALUES (?, ?, ?, ?)"
    );
    $stmt->execute([$materia_id, $dia_semana, $hora_inicio, $hora_fin]);

    echo json_encode(['ok' => true, 'id' => (int)$pdo->lastInsertId()]);
    exit;
}

http_response_code(400);
echo json_encode(['error' => 'Acción no reconocida.']);
