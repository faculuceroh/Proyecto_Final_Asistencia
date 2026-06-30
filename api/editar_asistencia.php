<?php
/*
 * api/editar_asistencia.php — Edición manual de asistencia (POST)
 * Body JSON: { clase_id, alumno_id, estado, hora_entrada? }
 * Solo para clases del día actual que pertenezcan al profesor.
 */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_auth(['profesor', 'admin']);

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); echo json_encode(['message' => 'Método no permitido']); exit;
}

$body      = json_decode(file_get_contents('php://input'), true) ?? [];
$clase_id  = (int)($body['clase_id']  ?? 0);
$alumno_id = (int)($body['alumno_id'] ?? 0);
$estado    = $body['estado'] ?? '';
$hora      = trim($body['hora_entrada'] ?? '');

$estados_validos = ['presente', 'tardanza', 'ausente'];
if (!$clase_id || !$alumno_id || !in_array($estado, $estados_validos)) {
    http_response_code(400);
    echo json_encode(['message' => 'Parámetros inválidos']);
    exit;
}

// Validar hora_entrada si viene
$hora_entrada = null;
if ($hora !== '') {
    if (!preg_match('/^\d{1,2}:\d{2}(:\d{2})?$/', $hora)) {
        http_response_code(400);
        echo json_encode(['message' => 'Formato de hora inválido']);
        exit;
    }
    $hora_entrada = strlen($hora) === 5 ? $hora . ':00' : $hora;
}

$prof_id = $_SESSION['usuario_id'];
$pdo     = getPDO();

// Verificar que la clase pertenece al profesor y es de hoy
$stmt = $pdo->prepare(
    'SELECT c.id FROM clases c
     JOIN materias m ON m.id = c.materia_id
     WHERE c.id = ? AND c.fecha = CURDATE()
       AND (m.profesor_id = ? OR m.profesor_2_id = ?)
     LIMIT 1'
);
$stmt->execute([$clase_id, $prof_id, $prof_id]);
if (!$stmt->fetch()) {
    http_response_code(403);
    echo json_encode(['message' => 'Clase no encontrada o no pertenece a tus clases de hoy']);
    exit;
}

// Verificar que el alumno está inscripto en la materia
$stmt = $pdo->prepare(
    'SELECT i.id FROM inscripciones i
     JOIN clases c ON c.materia_id = i.materia_id
     WHERE i.alumno_id = ? AND c.id = ? LIMIT 1'
);
$stmt->execute([$alumno_id, $clase_id]);
if (!$stmt->fetch()) {
    http_response_code(403);
    echo json_encode(['message' => 'El alumno no está inscripto en esta materia']);
    exit;
}

// Si estado = ausente y no hay hora → eliminar el registro (si existe)
if ($estado === 'ausente' && !$hora_entrada) {
    $pdo->prepare('DELETE FROM asistencias WHERE alumno_id = ? AND clase_id = ?')
        ->execute([$alumno_id, $clase_id]);
    echo json_encode(['ok' => true, 'estado' => 'ausente']);
    exit;
}

// UPSERT
if ($hora_entrada) {
    $pdo->prepare(
        'INSERT INTO asistencias (alumno_id, clase_id, hora_entrada, estado)
         VALUES (?, ?, ?, ?)
         ON DUPLICATE KEY UPDATE hora_entrada = VALUES(hora_entrada), estado = VALUES(estado)'
    )->execute([$alumno_id, $clase_id, $hora_entrada, $estado]);
} else {
    $pdo->prepare(
        'INSERT INTO asistencias (alumno_id, clase_id, estado)
         VALUES (?, ?, ?)
         ON DUPLICATE KEY UPDATE estado = VALUES(estado)'
    )->execute([$alumno_id, $clase_id, $estado]);
}

echo json_encode(['ok' => true, 'estado' => $estado, 'hora_entrada' => $hora_entrada]);
