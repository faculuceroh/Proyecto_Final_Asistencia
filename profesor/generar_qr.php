<?php
require_once '../includes/auth.php';
require_once '../models/Clase.php';

require_auth(['profesor', 'admin']);

$clase_id = (int) ($_GET['clase'] ?? 0);
if (!$clase_id) {
    header('Location: dashboard.php');
    exit;
}

// Verifica que la clase pertenezca al profesor logueado
$clase = Clase::getById($clase_id);

if (!$clase) {
    header('Location: dashboard.php');
    exit;
}

// Obtener detalles del profesor asignado a la materia
$db = getPDO();
$stmt = $db->prepare('SELECT profesor_id, profesor_2_id FROM materias WHERE id = ? LIMIT 1');
$stmt->execute([$clase['materia_id']]);
$materia_prof = $stmt->fetch();

// Validar que el profesor logueado tenga permiso para esta clase
if (!$materia_prof || ($materia_prof['profesor_id'] != $_SESSION['usuario_id'] && $materia_prof['profesor_2_id'] != $_SESSION['usuario_id'])) {
    header('Location: dashboard.php');
    exit;
}

// Agregar total de alumnos a la clase
$stmt = $db->prepare('SELECT COUNT(*) FROM inscripciones WHERE materia_id = ?');
$stmt->execute([$clase['materia_id']]);
$clase['total_alumnos'] = (int)$stmt->fetchColumn();

if ($clase['estado'] === 'finalizada') {
    header('Location: dashboard.php');
    exit;
}

// Parámetros del modal (materia, grupo, modalidad, aula, tipo)
$materia   = $_GET['materia']   ?? $clase['materia'];
$grupo     = $_GET['grupo']     ?? $clase['curso'];
$modalidad = $_GET['modalidad'] ?? $clase['modalidad'];
$tipo      = in_array($_GET['tipo'] ?? '', ['entrada','salida']) ? $_GET['tipo'] : 'entrada';
$aula      = $_GET['aula']      ?? $clase['aula'] ?? '';

$lugar = $modalidad === 'virtual' ? 'Virtual' : ($aula ?: 'Presencial');
$sub   = implode(' · ', array_filter([$grupo, $lugar, 'En curso']));

$partes    = explode(' ', $_SESSION['nombre']);
$iniciales = strtoupper(substr($partes[0], 0, 1) . substr($partes[1] ?? '', 0, 1));

// Carga la Vista
require_once '../views/profesor/generar_qr_view.php';

