<?php
require_once '../includes/auth.php';
require_once '../models/Materia.php';

require_auth(['secretaria', 'admin']);

$pdo = getPDO();

$materias = $pdo->query(
    "SELECT id, CONCAT(nombre, ' · ', curso) AS label FROM materias WHERE activo=1 ORDER BY nombre"
)->fetchAll(PDO::FETCH_ASSOC);

// Materia seleccionada (filtro GET)
$sel_materia = (int)($_GET['materia_id'] ?? 0) ?: ($materias[0]['id'] ?? 0);

// Alumnos inscriptos en la materia seleccionada
$inscritos = [];
$mat_info  = null;
if ($sel_materia) {
    $mat_info = Materia::getById($sel_materia);

    if ($mat_info) {
        $stmt = $pdo->prepare(
            "SELECT u.legajo, u.nombre, u.apellido, u.curso AS alumno_curso
             FROM inscripciones i
             JOIN usuarios u ON u.id = i.alumno_id
             WHERE i.materia_id = ?
             ORDER BY u.apellido, u.nombre"
        );
        $stmt->execute([$sel_materia]);
        $inscritos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

// Todos los alumnos para el buscador de alta manual
$todos_alumnos = $pdo->query(
    "SELECT id, legajo, nombre, apellido, curso
     FROM usuarios WHERE rol='alumno' AND activo=1 ORDER BY apellido, nombre"
)->fetchAll(PDO::FETCH_ASSOC);

$partes    = explode(' ', $_SESSION['nombre']);
$iniciales = strtoupper(substr($partes[0],0,1).substr($partes[1]??'',0,1));

// Carga la Vista
require_once '../views/secretaria/inscripciones_view.php';
