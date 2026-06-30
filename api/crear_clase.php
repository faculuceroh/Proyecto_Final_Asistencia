<?php
/*
 * api/crear_clase.php
 * POST { materia_id, fecha, hora_inicio, duracion_min, aula, modalidad }
 * Solo crea la clase si la materia pertenece al profesor logueado.
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

$body        = json_decode(file_get_contents('php://input'), true) ?? [];
$materia_id  = (int) ($body['materia_id']  ?? 0);
$fecha       = $body['fecha']       ?? '';
$hora_inicio = $body['hora_inicio'] ?? '';
$duracion    = (int) ($body['duracion_min'] ?? 90);
$aula        = trim($body['aula']   ?? '');
$modalidad   = in_array($body['modalidad'] ?? '', ['presencial','virtual'])
               ? $body['modalidad'] : 'presencial';

if (!$materia_id || !$fecha || !$hora_inicio) {
    http_response_code(400);
    echo json_encode(['message' => 'Materia, fecha y hora son obligatorios']);
    exit;
}

if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
    http_response_code(422);
    echo json_encode(['message' => 'Formato de fecha inválido (YYYY-MM-DD)']);
    exit;
}

$pdo = getPDO();

// Verifica que la materia pertenezca al profesor logueado
$stmt = $pdo->prepare(
    'SELECT id, nombre, curso FROM materias
     WHERE id = ? AND profesor_id = ? AND activo = 1 LIMIT 1'
);
$stmt->execute([$materia_id, $_SESSION['usuario_id']]);
$materia = $stmt->fetch();

if (!$materia) {
    http_response_code(403);
    echo json_encode(['message' => 'No tenés permiso para crear clases en esa materia']);
    exit;
}

// Evita duplicar clase en el mismo día y hora para esa materia
$dup = $pdo->prepare(
    'SELECT id FROM clases WHERE materia_id = ? AND fecha = ? AND hora_inicio = ? LIMIT 1'
);
$dup->execute([$materia_id, $fecha, $hora_inicio]);
if ($dup->fetch()) {
    http_response_code(409);
    echo json_encode(['message' => 'Ya existe una clase para esa materia en esa fecha y hora']);
    exit;
}

$ins = $pdo->prepare(
    'INSERT INTO clases (materia_id, fecha, hora_inicio, duracion_min, aula, modalidad)
     VALUES (?, ?, ?, ?, ?, ?)'
);
$ins->execute([$materia_id, $fecha, $hora_inicio, $duracion, $aula ?: null, $modalidad]);
$clase_id = (int) $pdo->lastInsertId();

echo json_encode([
    'ok'    => true,
    'clase' => [
        'id'          => $clase_id,
        'materia'     => $materia['nombre'],
        'curso'       => $materia['curso'],
        'fecha'       => $fecha,
        'fecha_fmt'   => date('d/m/Y', strtotime($fecha)),
        'hora_inicio' => substr($hora_inicio, 0, 5),
        'duracion_min'=> $duracion,
        'aula'        => $aula ?: '—',
        'modalidad'   => $modalidad,
        'estado'      => 'pendiente',
    ],
]);
