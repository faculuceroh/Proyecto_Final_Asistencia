<?php
require_once '../includes/auth.php';
require_once '../models/Clase.php';
require_once '../models/Materia.php';

require_auth(['profesor', 'admin']);

$prof_id = $_SESSION['usuario_id'];

$materia_id = (int)($_GET['materia_id'] ?? 0) ?: null;
$clase_id   = (int)($_GET['clase_id']   ?? 0) ?: null;

$partes    = explode(' ', $_SESSION['nombre']);
$iniciales = strtoupper(substr($partes[0],0,1).substr($partes[1]??'',0,1));

$badge_estado = [
    'pendiente'  => ['badge-muted',    'Pendiente'],
    'en_curso'   => ['badge-warning',  'En curso'],
    'finalizada' => ['badge-success',  'Finalizada'],
];
$badge_asist = ['presente'=>'badge-success','tardanza'=>'badge-warning','ausente'=>'badge-danger'];
$label_asist = ['presente'=>'Presente','tardanza'=>'Tardanza','ausente'=>'Ausente'];

$pdo = getPDO();

// ── Vista detalle de clase (alumnos) ─────────────────────────
if ($materia_id && $clase_id) {
    // Info de la materia
    $materia = Materia::getById($materia_id);
    if (!$materia || ($materia['profesor_id'] != $prof_id && $materia['profesor_2_id'] != $prof_id)) {
        header('Location: historial.php');
        exit;
    }

    $clase = Clase::getById($clase_id);
    if (!$clase || $clase['materia_id'] != $materia_id) {
        header("Location: historial.php?materia_id=$materia_id");
        exit;
    }

    // Todos los alumnos inscriptos + su asistencia en esta clase
    $stmt = $pdo->prepare(
        "SELECT u.apellido, u.nombre, u.legajo,
                 COALESCE(a.estado, 'ausente') AS estado,
                 TIME_FORMAT(a.hora_entrada, '%H:%i') AS hora_entrada
          FROM inscripciones i
          JOIN usuarios u ON u.id = i.alumno_id
          LEFT JOIN asistencias a ON a.alumno_id = i.alumno_id AND a.clase_id = ?
          WHERE i.materia_id = ?
          ORDER BY u.apellido, u.nombre"
    );
    $stmt->execute([$clase_id, $materia_id]);
    $alumnos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $presentes = count(array_filter($alumnos, fn($a) => in_array($a['estado'], ['presente','tardanza'])));
    $ausentes  = count(array_filter($alumnos, fn($a) => $a['estado'] === 'ausente'));
    $total_al  = count($alumnos);
    $pct_clase = $total_al ? round($presentes / $total_al * 100, 1) : 0;
}
// ── Vista clases de una materia ───────────────────────────────
elseif ($materia_id) {
    // Info de la materia
    $materia = Materia::getById($materia_id);
    if (!$materia || ($materia['profesor_id'] != $prof_id && $materia['profesor_2_id'] != $prof_id)) {
        header('Location: historial.php');
        exit;
    }

    $stmt = $pdo->prepare(
        "SELECT c.id, c.fecha, c.hora_inicio, c.duracion_min, c.estado,
                 (SELECT COUNT(*) FROM inscripciones WHERE materia_id = c.materia_id) AS inscriptos,
                 COALESCE(SUM(a.estado IN ('presente','tardanza')), 0) AS presentes,
                 COALESCE(SUM(a.estado = 'ausente'), 0) AS ausentes,
                 ROUND(SUM(a.estado IN ('presente','tardanza')) /
                       NULLIF((SELECT COUNT(*) FROM inscripciones WHERE materia_id = c.materia_id), 0) * 100, 1) AS pct
          FROM clases c
          LEFT JOIN asistencias a ON a.clase_id = c.id
          WHERE c.materia_id = ? AND c.fecha <= CURDATE()
          GROUP BY c.id
          ORDER BY c.fecha DESC"
    );
    $stmt->execute([$materia_id]);
    $clases = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $finalizadas = array_filter($clases, fn($c) => $c['estado'] === 'finalizada');
    $total_fin   = count($finalizadas);
    $total_pres  = array_sum(array_column($finalizadas, 'presentes'));
    $total_aus   = array_sum(array_column($finalizadas, 'ausentes'));
    $prom_pct    = $total_fin
        ? round(array_sum(array_column($finalizadas, 'pct')) / $total_fin, 1) : 0;
}
// ── Vista listado de materias ─────────────────────────────────
else {
    $stmt = $pdo->prepare(
        "SELECT m.id, m.nombre, m.curso, m.modalidad,
                 (SELECT COUNT(*) FROM clases WHERE materia_id = m.id AND estado = 'finalizada') AS clases_fin,
                 (SELECT COUNT(*) FROM inscripciones WHERE materia_id = m.id) AS inscriptos,
                 (SELECT ROUND(
                     SUM(a2.estado IN ('presente','tardanza')) /
                     NULLIF(COUNT(a2.id), 0) * 100, 1)
                  FROM asistencias a2
                  JOIN clases c2 ON c2.id = a2.clase_id
                  WHERE c2.materia_id = m.id AND c2.estado = 'finalizada') AS pct_asist,
                 (SELECT MIN(fecha) FROM clases
                  WHERE materia_id = m.id AND fecha >= CURDATE() AND estado = 'pendiente') AS proxima
          FROM materias m
          WHERE (m.profesor_id = ? OR m.profesor_2_id = ?) AND m.activo = 1
          ORDER BY m.nombre"
    );
    $stmt->execute([$prof_id, $prof_id]);
    $materias = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Carga la Vista
require_once '../views/profesor/historial_view.php';
