<?php
/*
 * api/registrar.php — Registra la asistencia del alumno (POST)
 * Llamado por qr-scanner.js después de leer el QR.
 *
 * Body JSON:
 *   { clase_id, token, tipo }   tipo = "entrada" | "salida"
 *
 * Respuesta OK:
 *   { "ok": true, "hora": "HH:MM" }
 */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_auth(['alumno']);

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['message' => 'Método no permitido']);
    exit;
}

$body     = json_decode(file_get_contents('php://input'), true) ?? [];
$clase_id = (int)  ($body['clase_id'] ?? 0);
$token    = trim($body['token']    ?? '');
$tipo     = in_array($body['tipo'] ?? '', ['entrada', 'salida'], true)
            ? $body['tipo'] : 'entrada';

if (!$clase_id || !$token) {
    http_response_code(400);
    echo json_encode(['message' => 'Datos incompletos']);
    exit;
}

$alumno_id = $_SESSION['usuario_id'];
$pdo       = getPDO();

// ── 1. Valida el token ────────────────────────────────────────
$stmt = $pdo->prepare(
    'SELECT id FROM qr_tokens
     WHERE token = ? AND clase_id = ? AND activo = 1 AND expira_en > NOW()
     LIMIT 1'
);
$stmt->execute([$token, $clase_id]);
if (!$stmt->fetch()) {
    http_response_code(422);
    echo json_encode(['message' => 'El QR expiró. Esperá que el profesor lo renueve.']);
    exit;
}

// ── 2. Verifica que el alumno esté inscripto en la materia ───
$stmt = $pdo->prepare(
    'SELECT i.id FROM inscripciones i
     JOIN clases c ON c.materia_id = i.materia_id
     WHERE i.alumno_id = ? AND c.id = ?
     LIMIT 1'
);
$stmt->execute([$alumno_id, $clase_id]);
if (!$stmt->fetch()) {
    http_response_code(403);
    echo json_encode(['message' => 'No estás inscripto en esta materia']);
    exit;
}

// ── 3. Verifica que la clase esté en curso ───────────────────
$stmt = $pdo->prepare('SELECT hora_inicio, estado FROM clases WHERE id = ? LIMIT 1');
$stmt->execute([$clase_id]);
$clase = $stmt->fetch();

if (!$clase || $clase['estado'] === 'finalizada') {
    http_response_code(403);
    echo json_encode(['message' => 'La clase ya fue finalizada']);
    exit;
}

// ── 4. Detecta doble escaneo ─────────────────────────────────
$stmt = $pdo->prepare(
    'SELECT hora_entrada, hora_salida FROM asistencias
     WHERE alumno_id = ? AND clase_id = ? LIMIT 1'
);
$stmt->execute([$alumno_id, $clase_id]);
$existente = $stmt->fetch();

if ($tipo === 'entrada' && $existente && $existente['hora_entrada']) {
    // Ya registró entrada: devuelve la hora original (no es un error)
    echo json_encode([
        'ok'   => true,
        'hora' => substr($existente['hora_entrada'], 0, 5),
        'aviso' => 'Ya tenías la entrada registrada.',
    ]);
    exit;
}
if ($tipo === 'salida' && $existente && $existente['hora_salida']) {
    echo json_encode([
        'ok'   => true,
        'hora' => substr($existente['hora_salida'], 0, 5),
        'aviso' => 'Ya tenías la salida registrada.',
    ]);
    exit;
}

// ── 5. Registra la asistencia ────────────────────────────────
$hora_ahora = date('H:i:s');

if ($tipo === 'entrada') {
    // Calcula si llegó a tiempo o tarde
    $tolerancia  = (int) $pdo->query(
        "SELECT valor FROM configuracion WHERE clave = 'tolerancia_minutos'"
    )->fetchColumn() ?: 10;
    $hora_limite = date('H:i:s', strtotime($clase['hora_inicio']) + $tolerancia * 60);
    $estado      = ($hora_ahora <= $hora_limite) ? 'presente' : 'tardanza';

    $pdo->prepare(
        'INSERT INTO asistencias (alumno_id, clase_id, hora_entrada, estado)
         VALUES (?, ?, ?, ?)
         ON DUPLICATE KEY UPDATE hora_entrada = VALUES(hora_entrada), estado = VALUES(estado)'
    )->execute([$alumno_id, $clase_id, $hora_ahora, $estado]);

} else {
    // salida: actualiza hora_salida (o crea el registro si no existe)
    $pdo->prepare(
        'INSERT INTO asistencias (alumno_id, clase_id, hora_salida, estado)
         VALUES (?, ?, ?, "presente")
         ON DUPLICATE KEY UPDATE hora_salida = VALUES(hora_salida)'
    )->execute([$alumno_id, $clase_id, $hora_ahora]);
}

echo json_encode([
    'ok'   => true,
    'hora' => date('H:i'),
]);
