<?php
/*
 * api/editar_asistencia.php — Edición manual del estado de asistencia (POST)
 * Body JSON: { clase_id, alumno_id, estado }
 * Solo para clases del día actual que pertenezcan al profesor.
 * No permite editar hora_entrada: el profesor solo corrige el estado.
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

$estados_validos = ['presente', 'tardanza', 'ausente'];
if (!$clase_id || !$alumno_id || !in_array($estado, $estados_validos)) {
    http_response_code(400);
    echo json_encode(['message' => 'Parámetros inválidos']);
    exit;
}

$prof_id = $_SESSION['usuario_id'];
$pdo     = getPDO();

// Verificar que la clase pertenece al profesor, es de hoy y ya arrancó (en_curso o finalizada)
$stmt = $pdo->prepare(
    'SELECT c.id FROM clases c
     JOIN materias m ON m.id = c.materia_id
     WHERE c.id = ? AND c.fecha = CURDATE() AND c.estado != "pendiente"
       AND (m.profesor_id = ? OR m.profesor_2_id = ?)
     LIMIT 1'
);
$stmt->execute([$clase_id, $prof_id, $prof_id]);
if (!$stmt->fetch()) {
    http_response_code(403);
    echo json_encode(['message' => 'La clase no pertenece a tus clases de hoy o todavía no arrancó']);
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

// Si estado = ausente → eliminar el registro (si existe), sin tocar horarios
if ($estado === 'ausente') {
    $pdo->prepare('DELETE FROM asistencias WHERE alumno_id = ? AND clase_id = ?')
        ->execute([$alumno_id, $clase_id]);
    echo json_encode(['ok' => true, 'estado' => 'ausente']);
    exit;
}

// UPSERT — solo el estado; hora_entrada/hora_salida quedan como estén (NULL si nunca escaneó)
$pdo->prepare(
    'INSERT INTO asistencias (alumno_id, clase_id, estado)
     VALUES (?, ?, ?)
     ON DUPLICATE KEY UPDATE estado = VALUES(estado)'
)->execute([$alumno_id, $clase_id, $estado]);

echo json_encode(['ok' => true, 'estado' => $estado]);
