<?php
require_once '../includes/auth.php';
require_once '../models/Usuario.php';
require_once '../models/Asistencia.php';
require_once '../models/Materia.php';
require_once '../models/Clase.php';

require_auth(['alumno']);

$alu_id = $_SESSION['usuario_id'];

// ── Datos del alumno ──────────────────────────────────────────
$alumno = Usuario::findById($alu_id);

// ── Stats globales ────────────────────────────────────────────
$stats = Asistencia::getStatsGlobal($alu_id);
$pct_global = $stats['pct_global'];
$presentes  = $stats['presentes'];
$ausentes   = $stats['ausentes'];

// ── Mis materias con horarios y % asistencia ──────────────────
$nombres_dia = [1=>'Lun',2=>'Mar',3=>'Mié',4=>'Jue',5=>'Vie',6=>'Sáb',7=>'Dom'];
$mis_materias = Asistencia::getAsistenciaMateriaResumen($alu_id);
$total_materias = count($mis_materias);

foreach ($mis_materias as &$mat) {
    $mat['horarios'] = Materia::getHorarios($mat['id']);
}
unset($mat);

// ── Clases de hoy ─────────────────────────────────────────────
$clases_hoy = Clase::getClasesHoyAlumno($alu_id);

// ── Asistencia reciente ───────────────────────────────────────
$recientes = Asistencia::getRecientes($alu_id, 5);

// ── Helpers ───────────────────────────────────────────────────
$estado_class = ['en_curso' => 'state-encurso', 'pendiente' => 'state-pendiente', 'finalizada' => 'state-finalizada'];
$badge_est    = ['presente' => ['badge-success','Presente'], 'tardanza' => ['badge-warning','Tarde'], 'ausente' => ['badge-danger','Ausente']];
$partes       = explode(' ', $_SESSION['nombre']);
$iniciales    = strtoupper(substr($partes[0],0,1).substr($partes[1]??'',0,1));
$nombre_corto = $partes[0];

// Carga la Vista
require_once '../views/alumno/dashboard_view.php';

