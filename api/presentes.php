<?php
/*
 * api/presentes.php — Devuelve el conteo en vivo de presentes (GET)
 * Llamado por qr-display.js en la pantalla del profesor.
 *
 * Parámetros GET:
 *   clase_id  (int, requerido)
 *
 * Respuesta:
 *   {
 *     "presentes": 15,
 *     "total": 30,
 *     "ultimos": [
 *       { "nombre": "Lucía Gómez", "iniciales": "LG", "hora": "10:05" },
 *       ...
 *     ]
 *   }
 */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_auth(['profesor', 'admin']);

header('Content-Type: application/json; charset=utf-8');

$clase_id = (int) ($_GET['clase_id'] ?? 0);
if (!$clase_id) {
    http_response_code(400);
    echo json_encode(['message' => 'clase_id requerido']);
    exit;
}

$pdo = getPDO();

// Total de alumnos inscriptos en la materia de esta clase
$stmt = $pdo->prepare(
    'SELECT COUNT(*) FROM inscripciones i
     JOIN clases c ON c.materia_id = i.materia_id
     WHERE c.id = ?'
);
$stmt->execute([$clase_id]);
$total = (int) $stmt->fetchColumn();

// Alumnos presentes (con entrada registrada)
$stmt = $pdo->prepare(
    'SELECT COUNT(*) FROM asistencias
     WHERE clase_id = ? AND hora_entrada IS NOT NULL'
);
$stmt->execute([$clase_id]);
$presentes = (int) $stmt->fetchColumn();

// Últimos 5 que escanearon (para la lista en vivo)
$stmt = $pdo->prepare(
    'SELECT u.nombre, u.apellido,
            TIME_FORMAT(a.hora_entrada, "%H:%i") AS hora
     FROM asistencias a
     JOIN usuarios u ON u.id = a.alumno_id
     WHERE a.clase_id = ? AND a.hora_entrada IS NOT NULL
     ORDER BY a.updated_at DESC
     LIMIT 5'
);
$stmt->execute([$clase_id]);
$ultimos = array_map(function ($row) {
    return [
        'nombre'    => $row['nombre'] . ' ' . $row['apellido'],
        'iniciales' => strtoupper(substr($row['nombre'], 0, 1) . substr($row['apellido'], 0, 1)),
        'hora'      => $row['hora'],
    ];
}, $stmt->fetchAll());

echo json_encode([
    'presentes' => $presentes,
    'total'     => $total,
    'ultimos'   => $ultimos,
]);
