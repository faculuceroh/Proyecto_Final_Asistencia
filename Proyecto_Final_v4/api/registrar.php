<?php
/*
 * api/registrar.php — Registra la asistencia del alumno (POST)
 * Body JSON: { aula_token: "..." }
 */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_auth(['alumno']);

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); echo json_encode(['message' => 'Método no permitido']); exit;
}

$body       = json_decode(file_get_contents('php://input'), true) ?? [];
$aula_token = trim($body['aula_token'] ?? '');

if (!$aula_token) {
    http_response_code(400); echo json_encode(['message' => 'Token de aula no recibido']); exit;
}

$alumno_id = $_SESSION['usuario_id'];
$pdo       = getPDO();

// ── 1. Buscar el aula por token ───────────────────────────────
$stmt = $pdo->prepare('SELECT id, nombre FROM aulas WHERE token = ? AND activo = 1 LIMIT 1');
$stmt->execute([$aula_token]);
$aula = $stmt->fetch();

if (!$aula) {
    http_response_code(422);
    echo json_encode(['message' => 'Código QR no válido o el aula fue desactivada.']);
    exit;
}

// ── 2. Buscar sesión activa en este aula ─────────────────────
$stmt = $pdo->prepare(
    'SELECT id, clase_id, tipo, expira_en
     FROM qr_sesiones
     WHERE aula_id = ? AND activo = 1
     LIMIT 1'
);
$stmt->execute([$aula['id']]);
$sesion = $stmt->fetch();

if (!$sesion) {
    http_response_code(422);
    echo json_encode(['message' => 'El QR del aula "' . $aula['nombre'] . '" no está habilitado en este momento.']);
    exit;
}

// ── 3. Verificar expiración de QR de salida ──────────────────
if ($sesion['tipo'] === 'salida' && $sesion['expira_en'] && strtotime($sesion['expira_en']) < time()) {
    $pdo->prepare('UPDATE qr_sesiones SET activo = 0 WHERE id = ?')->execute([$sesion['id']]);
    $pdo->prepare('UPDATE clases SET estado = "finalizada" WHERE id = ?')->execute([$sesion['clase_id']]);
    http_response_code(422);
    echo json_encode(['message' => 'El tiempo para registrar la salida expiró.']);
    exit;
}

$clase_id = (int)$sesion['clase_id'];
$tipo     = $sesion['tipo'];

// ── 4. Verificar que el alumno está inscripto en la materia ──
$stmt = $pdo->prepare(
    'SELECT i.id FROM inscripciones i
     JOIN clases c ON c.materia_id = i.materia_id
     WHERE i.alumno_id = ? AND c.id = ? LIMIT 1'
);
$stmt->execute([$alumno_id, $clase_id]);
if (!$stmt->fetch()) {
    http_response_code(403);
    echo json_encode(['message' => 'No estás inscripto en la materia de esta clase.']);
    exit;
}

// ── 5. Verificar estado de la clase ──────────────────────────
$stmt = $pdo->prepare('SELECT hora_inicio, estado FROM clases WHERE id = ? LIMIT 1');
$stmt->execute([$clase_id]);
$clase = $stmt->fetch();

if (!$clase || $clase['estado'] === 'finalizada') {
    http_response_code(403);
    echo json_encode(['message' => 'La clase ya fue finalizada.']);
    exit;
}

// ── 6. Detectar doble escaneo ─────────────────────────────────
$stmt = $pdo->prepare(
    'SELECT hora_entrada, hora_salida FROM asistencias WHERE alumno_id = ? AND clase_id = ? LIMIT 1'
);
$stmt->execute([$alumno_id, $clase_id]);
$existente = $stmt->fetch();

if ($tipo === 'entrada' && $existente && $existente['hora_entrada']) {
    echo json_encode(['ok' => true, 'hora' => substr($existente['hora_entrada'], 0, 5), 'tipo' => 'entrada',
                      'aviso' => 'Ya tenías la entrada registrada.']);
    exit;
}
if ($tipo === 'salida' && $existente && $existente['hora_salida']) {
    echo json_encode(['ok' => true, 'hora' => substr($existente['hora_salida'], 0, 5), 'tipo' => 'salida',
                      'aviso' => 'Ya tenías la salida registrada.']);
    exit;
}

// ── 7. Registrar la asistencia ───────────────────────────────
$hora_ahora = date('H:i:s');

if ($tipo === 'entrada') {
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
    $pdo->prepare(
        'INSERT INTO asistencias (alumno_id, clase_id, hora_salida, estado)
         VALUES (?, ?, ?, "presente")
         ON DUPLICATE KEY UPDATE hora_salida = VALUES(hora_salida)'
    )->execute([$alumno_id, $clase_id, $hora_ahora]);
}

echo json_encode(['ok' => true, 'hora' => date('H:i'), 'tipo' => $tipo]);
