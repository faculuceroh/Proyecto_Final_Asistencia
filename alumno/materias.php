<?php
require_once '../includes/auth.php';
require_once '../models/Materia.php';
require_once '../models/Usuario.php';

require_auth(['alumno']);

$alu_id = $_SESSION['usuario_id'];

// Materias inscriptas con % de asistencia
$materias = Materia::getInscriptas($alu_id);

// Stats resumen
$pct_global = Materia::getInscriptasPctGlobal($alu_id);

$partes    = explode(' ', $_SESSION['nombre']);
$iniciales = strtoupper(substr($partes[0],0,1).substr($partes[1]??'',0,1));

$alumno = Usuario::findById($alu_id);
$curso = $alumno['curso'] ?? '';

// Carga la Vista
require_once '../views/alumno/materias_view.php';


