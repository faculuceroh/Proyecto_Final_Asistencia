<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';

require_auth(['secretaria', 'admin']);

$pdo = getPDO();

$cursos = $pdo->query("SELECT nombre FROM cursos ORDER BY nombre")->fetchAll(PDO::FETCH_COLUMN);

$profesores = $pdo->query(
    "SELECT id, CONCAT(nombre,' ',apellido) AS nombre
     FROM usuarios WHERE rol='profesor' AND activo=1 ORDER BY apellido"
)->fetchAll(PDO::FETCH_ASSOC);

// Materias con horario concatenado
$materias = $pdo->query(
    "SELECT m.id, m.nombre, m.codigo, m.curso, m.modalidad,
            COALESCE(CONCAT(u.nombre,' ',u.apellido),'—') AS profesor,
            COALESCE(CONCAT(u2.nombre,' ',u2.apellido),'') AS profesor_2,
            (SELECT GROUP_CONCAT(
               CASE dia_semana
                 WHEN 1 THEN 'Lun' WHEN 2 THEN 'Mar' WHEN 3 THEN 'Mié'
                 WHEN 4 THEN 'Jue' WHEN 5 THEN 'Vie' WHEN 6 THEN 'Sáb' ELSE 'Dom'
               END ORDER BY dia_semana SEPARATOR ', ')
             FROM materia_horarios WHERE materia_id = m.id) AS dias,
            (SELECT CONCAT(TIME_FORMAT(hora_inicio,'%H:%i'),' - ',TIME_FORMAT(hora_fin,'%H:%i'))
             FROM materia_horarios WHERE materia_id = m.id LIMIT 1) AS horario
     FROM materias m
     LEFT JOIN usuarios u  ON u.id  = m.profesor_id
     LEFT JOIN usuarios u2 ON u2.id = m.profesor_2_id
     WHERE m.activo = 1 ORDER BY m.nombre"
)->fetchAll(PDO::FETCH_ASSOC);

$partes    = explode(' ', $_SESSION['nombre']);
$iniciales = strtoupper(substr($partes[0],0,1).substr($partes[1]??'',0,1));

$dias_semana = [1=>'Lun',2=>'Mar',3=>'Mié',4=>'Jue',5=>'Vie',6=>'Sáb',7=>'Dom'];

// Carga la Vista
require_once '../views/secretaria/materias_view.php';
