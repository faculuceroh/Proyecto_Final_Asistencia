<?php
require_once '../includes/auth.php';
require_once '../models/Asistencia.php';
require_once '../models/Materia.php';

require_auth(['alumno']);

$alu_id    = $_SESSION['usuario_id'];
$materia_id = (int)($_GET['materia_id'] ?? 0) ?: null;

// Esta página ahora es solo el detalle clase-por-clase de una materia. El
// listado de materias vive en materias.php (tiene más info: profesor,
// clases dictadas, aviso de riesgo de libre).
if (!$materia_id) {
    header('Location: materias.php');
    exit;
}

$partes    = explode(' ', $_SESSION['nombre']);
$iniciales = strtoupper(substr($partes[0],0,1).substr($partes[1]??'',0,1));

$badge_asist = ['presente'=>'badge-success','tardanza'=>'badge-warning','ausente'=>'badge-danger','sin_registro'=>'badge-muted'];
$label_asist = ['presente'=>'Presente','tardanza'=>'Tarde','ausente'=>'Ausente','sin_registro'=>'Sin registro'];

// Verificar que el alumno esté inscripto
if (!Materia::isInscripto($alu_id, $materia_id)) {
    header('Location: materias.php');
    exit;
}

// Info de la materia
$materia = Materia::getById($materia_id);
if (!$materia) {
    header('Location: materias.php');
    exit;
}

// Clases de la materia hasta hoy, con asistencia del alumno
$clases = Asistencia::getAsistenciasPorMateria($alu_id, $materia_id);

// Stats
$finalizadas = array_filter($clases, fn($c) => $c['estado_clase'] === 'finalizada');
$total_fin   = count($finalizadas);
$presentes   = count(array_filter($finalizadas, fn($c) => in_array($c['estado_asist'], ['presente','tardanza'])));
$ausentes    = count(array_filter($finalizadas, fn($c) => $c['estado_asist'] === 'ausente'));
$pct         = $total_fin ? round($presentes / $total_fin * 100, 1) : 0;

// Carga la Vista
require_once '../views/alumno/historial_view.php';

