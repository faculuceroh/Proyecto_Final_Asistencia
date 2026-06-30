<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_auth(['secretaria', 'admin']);

header('Content-Type: application/json');

$body = json_decode(file_get_contents('php://input'), true);

$materia_id  = (int)($body['materia_id']  ?? 0);
$fecha_inicio = trim($body['fecha_inicio'] ?? '');
$fecha_fin    = trim($body['fecha_fin']    ?? '');
$horario_ids  = array_map('intval', (array)($body['horario_ids'] ?? []));

if (!$materia_id || !$fecha_inicio || !$fecha_fin || empty($horario_ids)) {
    http_response_code(400);
    echo json_encode(['error' => 'Datos incompletos.']);
    exit;
}

if ($fecha_inicio > $fecha_fin) {
    http_response_code(400);
    echo json_encode(['error' => 'La fecha de inicio no puede ser posterior al fin.']);
    exit;
}

$pdo = getPDO();

// Verificar materia
$stmt = $pdo->prepare("SELECT id, modalidad FROM materias WHERE id = ? AND activo = 1 LIMIT 1");
$stmt->execute([$materia_id]);
$materia = $stmt->fetch();
if (!$materia) {
    http_response_code(404);
    echo json_encode(['error' => 'Materia no encontrada.']);
    exit;
}

// Cargar solo los horarios seleccionados que pertenecen a esta materia
if (count($horario_ids) === 0) {
    echo json_encode(['ok' => true, 'generadas' => 0, 'saltadas' => 0]);
    exit;
}

$placeholders = implode(',', array_fill(0, count($horario_ids), '?'));
$stmt = $pdo->prepare(
    "SELECT id, dia_semana, hora_inicio, hora_fin
     FROM materia_horarios
     WHERE id IN ($placeholders) AND materia_id = ?"
);
$stmt->execute([...$horario_ids, $materia_id]);
$horarios = $stmt->fetchAll();

if (empty($horarios)) {
    http_response_code(400);
    echo json_encode(['error' => 'No se encontraron horarios válidos para esta materia.']);
    exit;
}

// Indexar por dia_semana para búsqueda rápida
// Puede haber varios horarios el mismo día (poco frecuente pero posible)
$por_dia = []; // [dia_semana => [horario, ...]]
foreach ($horarios as $h) {
    $por_dia[(int)$h['dia_semana']][] = $h;
}

// INSERT: usa IGNORE para no duplicar si ya existe (materia_id + fecha + hora_inicio)
$insert = $pdo->prepare(
    "INSERT IGNORE INTO clases (materia_id, fecha, hora_inicio, duracion_min, modalidad, estado)
     VALUES (?, ?, ?, ?, ?, 'pendiente')"
);

$generadas = 0;
$saltadas  = 0;

$current = new DateTime($fecha_inicio . ' 00:00:00');
$limite  = new DateTime($fecha_fin   . ' 00:00:00');

while ($current <= $limite) {
    // PHP: getDay() devuelve 0=Dom..6=Sáb, convertimos a 1=Lun..7=Dom
    $dow = (int)$current->format('N'); // ISO 8601: 1=Mon..7=Sun

    if (isset($por_dia[$dow])) {
        foreach ($por_dia[$dow] as $h) {
            // Calcular duración en minutos
            [$hh, $mm] = explode(':', substr($h['hora_inicio'], 0, 5));
            [$fh, $fm] = explode(':', substr($h['hora_fin'],    0, 5));
            $duracion = ((int)$fh * 60 + (int)$fm) - ((int)$hh * 60 + (int)$mm);
            if ($duracion <= 0) $duracion = 90;

            $insert->execute([
                $materia_id,
                $current->format('Y-m-d'),
                substr($h['hora_inicio'], 0, 5),
                $duracion,
                $materia['modalidad'],
            ]);

            if ($insert->rowCount() > 0) {
                $generadas++;
            } else {
                $saltadas++;
            }
        }
    }

    $current->modify('+1 day');
}

echo json_encode(['ok' => true, 'generadas' => $generadas, 'saltadas' => $saltadas]);
