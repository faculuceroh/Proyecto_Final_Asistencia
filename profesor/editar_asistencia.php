<?php
require_once '../includes/auth.php';
require_once '../models/Clase.php';

require_auth(['profesor', 'admin']);

$prof_id  = $_SESSION['usuario_id'];
$clase_id = (int)($_GET['clase_id'] ?? 0);

if (!$clase_id) { header('Location: dashboard.php'); exit; }

// Verificar que la clase pertenece al profesor
$clase = Clase::getById($clase_id);

if (!$clase) { header('Location: dashboard.php'); exit; }

// Obtener detalles del profesor asignado a la materia
$db = getPDO();
$stmt = $db->prepare('SELECT profesor_id, profesor_2_id FROM materias WHERE id = ? LIMIT 1');
$stmt->execute([$clase['materia_id']]);
$materia_prof = $stmt->fetch();

// Validar que el profesor logueado tenga permiso para esta clase
if (!$materia_prof || ($materia_prof['profesor_id'] != $prof_id && $materia_prof['profesor_2_id'] != $prof_id)) {
    header('Location: dashboard.php');
    exit;
}

// Alumnos inscriptos con su estado de asistencia actual
$stmt = $db->prepare(
    'SELECT u.id, u.legajo, u.nombre, u.apellido,
            COALESCE(a.estado, "ausente")        AS estado,
            TIME_FORMAT(a.hora_entrada, "%H:%i") AS hora_entrada,
            TIME_FORMAT(a.hora_salida,  "%H:%i") AS hora_salida
     FROM inscripciones i
     JOIN usuarios u ON u.id = i.alumno_id
     LEFT JOIN asistencias a ON a.alumno_id = i.alumno_id AND a.clase_id = ?
     WHERE i.materia_id = ?
     ORDER BY u.apellido, u.nombre'
);
$stmt->execute([$clase_id, $clase['materia_id']]);
$alumnos = $stmt->fetchAll(PDO::FETCH_ASSOC);

$badge = [
    'presente' => 'badge-success',
    'tardanza' => 'badge-warning',
    'ausente'  => 'badge-muted',
];

$partes    = explode(' ', $_SESSION['nombre']);
$iniciales = strtoupper(substr($partes[0],0,1).substr($partes[1]??'',0,1));

$estado_label = [
    'pendiente'  => ['badge-muted',    'Pendiente'],
    'en_curso'   => ['badge-warning',  'En curso'],
    'finalizada' => ['badge-success',  'Finalizada'],
];
[$badge_clase, $label_clase] = $estado_label[$clase['estado']] ?? ['badge-muted', $clase['estado']];

// Carga la Vista
require_once '../views/profesor/editar_asistencia_view.php';
>
