<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';

require_auth(['profesor', 'admin']);

$prof_id = $_SESSION['usuario_id'];
$pdo     = getPDO();

$clase_preselect = (int) ($_GET['clase_id'] ?? 0);

// Si se llegó desde el botón "Importar Teams" de una clase puntual del dashboard,
// se restringe la lista a esa única clase. Sin clase_id, se listan todas las
// clases virtuales del profesor (acceso directo desde el menú).
$condicion_clase = $clase_preselect ? 'AND c.id = :clase_id' : '';

$stmt = $pdo->prepare(
    "SELECT c.id, c.fecha, c.hora_inicio, c.duracion_min, c.estado,
            m.nombre AS materia_nombre, m.curso
     FROM clases c
     JOIN materias m ON m.id = c.materia_id
     WHERE c.modalidad = 'virtual'
       AND c.estado != 'suspendida'
       AND (m.profesor_id = :pid OR m.profesor_2_id = :pid2)
       $condicion_clase
     ORDER BY c.fecha DESC, c.hora_inicio DESC
     LIMIT 100"
);
$params = [':pid' => $prof_id, ':pid2' => $prof_id];
if ($clase_preselect) {
    $params[':clase_id'] = $clase_preselect;
}
$stmt->execute($params);
$clases_virtuales = $stmt->fetchAll();

$partes    = explode(' ', $_SESSION['nombre']);
$iniciales = strtoupper(substr($partes[0], 0, 1) . substr($partes[1] ?? '', 0, 1));

require_once '../views/profesor/importar_teams_view.php';
