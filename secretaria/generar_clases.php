<?php
require_once '../includes/auth.php';
require_once '../models/Materia.php';

require_auth(['secretaria', 'admin']);

$pdo = getPDO();

$materia_id = (int)($_GET['materia_id'] ?? 0);
if (!$materia_id) { header('Location: materias.php'); exit; }

$materia = Materia::getById($materia_id);
if (!$materia) { header('Location: materias.php'); exit; }

// Obtener detalles del profesor asignado a la materia
$stmt = $pdo->prepare(
    "SELECT COALESCE(CONCAT(u.nombre,' ',u.apellido),'Sin asignar') AS profesor
     FROM materias m
     LEFT JOIN usuarios u ON u.id = m.profesor_id
     WHERE m.id = ? LIMIT 1"
);
$stmt->execute([$materia_id]);
$prof_row = $stmt->fetch();
$materia['profesor'] = $prof_row ? $prof_row['profesor'] : 'Sin asignar';

// Horarios de esta materia
$stmt = $pdo->prepare(
    "SELECT id, dia_semana, hora_inicio, hora_fin
     FROM materia_horarios WHERE materia_id = ? ORDER BY dia_semana"
);
$stmt->execute([$materia_id]);
$horarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

$nombres_dia = [1=>'Lunes',2=>'Martes',3=>'Miércoles',4=>'Jueves',5=>'Viernes',6=>'Sábado',7=>'Domingo'];

$partes    = explode(' ', $_SESSION['nombre']);
$iniciales = strtoupper(substr($partes[0],0,1).substr($partes[1]??'',0,1));

// Carga la Vista
require_once '../views/secretaria/generar_clases_view.php';
