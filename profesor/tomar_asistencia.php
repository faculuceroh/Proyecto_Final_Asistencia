<?php
require_once '../includes/auth.php';
require_once '../models/Clase.php';
require_once '../models/Aula.php';

require_auth(['profesor', 'admin']);

$prof_id  = $_SESSION['usuario_id'];
$clase_id = (int)($_GET['clase_id'] ?? 0);

if (!$clase_id) { header('Location: dashboard.php'); exit; }

// Verificar que la clase pertenece al profesor
$clase = Clase::getById($clase_id);

if (!$clase) { header('Location: dashboard.php'); exit; }
if ($clase['modalidad'] === 'virtual') {
    header('Location: importar_teams.php?clase_id=' . $clase_id);
    exit;
}
if ($clase['estado'] === 'finalizada') { header('Location: dashboard.php'); exit; }

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

// Aulas disponibles
$aulas = Aula::getAll();

// Total inscriptos
$stmt = $db->prepare(
    'SELECT COUNT(*) FROM inscripciones i
     JOIN clases c ON c.materia_id = i.materia_id
     WHERE c.id = ?'
);
$stmt->execute([$clase_id]);
$total_inscriptos = (int)$stmt->fetchColumn();

$partes    = explode(' ', $_SESSION['nombre']);
$iniciales = strtoupper(substr($partes[0],0,1).substr($partes[1]??'',0,1));

// Base URL para el QR del aula
$proto   = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host    = $_SERVER['HTTP_HOST'];
$base_qr = $proto . '://' . $host . '/asistencia/alumno/escanear.php?aula=';

// Carga la Vista
require_once '../views/profesor/tomar_asistencia_view.php';
