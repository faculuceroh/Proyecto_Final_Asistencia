<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_auth(['secretaria', 'admin']);

$pdo  = getPDO();
$rows = $pdo->query(
    "SELECT apellido, nombre, legajo, curso, materia,
            clases_presentes, porcentaje
     FROM v_alumnos_en_riesgo
     ORDER BY porcentaje ASC, apellido ASC"
)->fetchAll(PDO::FETCH_ASSOC);

$fecha = date('Y-m-d');
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="alumnos_en_riesgo_' . $fecha . '.csv"');
header('Cache-Control: no-cache');

$out = fopen('php://output', 'w');
fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF)); // BOM UTF-8 para Excel

fputcsv($out, ['Apellido', 'Nombre', 'Legajo', 'Curso', 'Materia', 'Clases asistidas', '% Asistencia'], ';');

foreach ($rows as $r) {
    fputcsv($out, [
        $r['apellido'],
        $r['nombre'],
        $r['legajo'],
        $r['curso'],
        $r['materia'],
        $r['clases_presentes'],
        number_format((float)($r['porcentaje'] ?? 0), 1, ',', '') . '%',
    ], ';');
}

fclose($out);
