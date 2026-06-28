<?php
/*
 * api/exportar.php — Descarga CSV de asistencias (GET)
 *
 * Modos:
 *   ?clase_id=X               → exporta una clase específica
 *   ?materia_id=X[&fecha=Y]   → exporta historial de una materia (con fecha opcional)
 */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_auth(['admin', 'secretaria', 'profesor']);

$clase_id   = (int) ($_GET['clase_id']   ?? 0);
$materia_id = (int) ($_GET['materia_id'] ?? 0);
$fecha_get  = $_GET['fecha'] ?? '';

if (!$clase_id && !$materia_id) {
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode(['message' => 'clase_id o materia_id requerido']);
    exit;
}

$pdo = getPDO();

// ── Modo: exportar historial de una materia ───────────────────
if ($materia_id && !$clase_id) {
    $stmt = $pdo->prepare('SELECT nombre, curso FROM materias WHERE id = ? LIMIT 1');
    $stmt->execute([$materia_id]);
    $mat = $stmt->fetch();
    if (!$mat) {
        http_response_code(404);
        header('Content-Type: application/json');
        echo json_encode(['message' => 'Materia no encontrada']);
        exit;
    }

    $where  = 'WHERE c.materia_id = ?';
    $params = [$materia_id];
    if ($fecha_get) { $where .= ' AND c.fecha = ?'; $params[] = $fecha_get; }

    $stmt = $pdo->prepare(
        "SELECT u.apellido, u.nombre, u.legajo, u.curso AS alumno_curso,
                c.fecha,
                COALESCE(TIME_FORMAT(a.hora_entrada,'%H:%i'),'—') AS entrada,
                COALESCE(TIME_FORMAT(a.hora_salida, '%H:%i'),'—') AS salida,
                COALESCE(a.estado,'ausente')                        AS estado
         FROM asistencias a
         JOIN usuarios u ON u.id = a.alumno_id
         JOIN clases c ON c.id = a.clase_id
         $where
         ORDER BY c.fecha DESC, u.apellido, u.nombre"
    );
    $stmt->execute($params);
    $filas = $stmt->fetchAll();

    $mat_slug = preg_replace('/[^a-z0-9]+/i', '_', $mat['nombre']);
    $suf      = $fecha_get ? str_replace('-', '', $fecha_get) : 'completo';
    $filename = "historial_{$mat_slug}_{$suf}.csv";

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: no-cache');
    $out = fopen('php://output', 'w');
    fputs($out, "\xEF\xBB\xBF");
    fputcsv($out, ['Apellido','Nombre','Legajo','Curso','Fecha','Entrada','Salida','Estado'], ';');
    foreach ($filas as $f) {
        fputcsv($out, [
            $f['apellido'], $f['nombre'], $f['legajo'], $f['alumno_curso'],
            date('d/m/Y', strtotime($f['fecha'])),
            $f['entrada'], $f['salida'], ucfirst($f['estado']),
        ], ';');
    }
    fclose($out);
    exit;
}

// ── Modo: exportar una clase específica ──────────────────────
$stmt = $pdo->prepare(
    'SELECT m.nombre AS materia, m.curso, c.fecha
     FROM clases c JOIN materias m ON m.id = c.materia_id
     WHERE c.id = ? LIMIT 1'
);
$stmt->execute([$clase_id]);
$clase = $stmt->fetch();

if (!$clase) {
    http_response_code(404);
    header('Content-Type: application/json');
    echo json_encode(['message' => 'Clase no encontrada']);
    exit;
}

$stmt = $pdo->prepare(
    'SELECT u.apellido, u.nombre, u.legajo, u.curso,
            COALESCE(TIME_FORMAT(a.hora_entrada,"%H:%i"), "—") AS entrada,
            COALESCE(TIME_FORMAT(a.hora_salida, "%H:%i"), "—") AS salida,
            COALESCE(a.estado, "ausente")                       AS estado
     FROM inscripciones i
     JOIN usuarios u ON u.id = i.alumno_id
     LEFT JOIN asistencias a ON a.alumno_id = i.alumno_id AND a.clase_id = ?
     WHERE i.materia_id = (SELECT materia_id FROM clases WHERE id = ?)
     ORDER BY u.apellido, u.nombre'
);
$stmt->execute([$clase_id, $clase_id]);
$filas = $stmt->fetchAll();

$fecha_str = str_replace('-', '', $clase['fecha']);
$mat_slug  = preg_replace('/[^a-z0-9]+/i', '_', $clase['materia']);
$filename  = "asistencia_{$mat_slug}_{$fecha_str}.csv";

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: no-cache');

$out = fopen('php://output', 'w');
fputs($out, "\xEF\xBB\xBF");

fputcsv($out, ['Apellido','Nombre','Legajo','Curso','Entrada','Salida','Estado'], ';');
foreach ($filas as $f) {
    fputcsv($out, [
        $f['apellido'], $f['nombre'], $f['legajo'],
        $f['curso'],    $f['entrada'], $f['salida'],
        ucfirst($f['estado']),
    ], ';');
}
fclose($out);
