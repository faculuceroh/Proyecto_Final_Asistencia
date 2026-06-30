<?php
require_once '../includes/auth.php';
require_once '../models/Clase.php';
require_once '../models/Materia.php';

require_auth(['profesor']);

$profesor_id = $_SESSION['usuario_id'];

// Clases del día (considera profesor principal y segundo)
$clases = Clase::getClasesHoyProfesor($profesor_id);

// Materias asignadas con horario semanal y próxima clase
$nombres_dia = [1=>'Lun',2=>'Mar',3=>'Mié',4=>'Jue',5=>'Vie',6=>'Sáb',7=>'Dom'];
$mis_materias = Clase::getMateriasConProximaClaseProfesor($profesor_id);

// Horarios por materia
foreach ($mis_materias as &$mat) {
    $mat['horarios'] = Materia::getHorarios($mat['id']);
}
unset($mat);

// Helpers de presentación
$estado_class = [
    'en_curso'   => 'state-encurso',
    'pendiente'  => 'state-pendiente',
    'finalizada' => 'state-finalizada',
];
$estado_badge = [
    'en_curso'   => ['badge-warning', 'En curso'],
    'pendiente'  => ['badge-muted',   'Pendiente'],
    'finalizada' => ['badge-success', 'Finalizada'],
];

$dias      = ['domingo','lunes','martes','miércoles','jueves','viernes','sábado'];
$fecha_hoy = ucfirst($dias[date('w')]) . ' ' . date('d/m/Y');

$partes    = explode(' ', $_SESSION['nombre']);
$iniciales = strtoupper(substr($partes[0], 0, 1) . substr($partes[1] ?? '', 0, 1));

$en_curso  = count(array_filter($clases, fn($c) => $c['estado'] === 'en_curso'));

// Carga la Vista
require_once '../views/profesor/dashboard_view.php';

