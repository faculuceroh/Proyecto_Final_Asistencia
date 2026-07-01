<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';

require_auth(['profesor', 'admin']);

$prof_id = $_SESSION['usuario_id'];
$pdo     = getPDO();

// Clases virtuales de este profesor (ambos campos profesor_id y profesor_2_id)
$stmt = $pdo->prepare(
    "SELECT c.id, c.fecha, c.hora_inicio, c.duracion_min, c.estado,
            m.nombre AS materia_nombre, m.curso
     FROM clases c
     JOIN materias m ON m.id = c.materia_id
     WHERE c.modalidad = 'virtual'
       AND (m.profesor_id = :pid OR m.profesor_2_id = :pid2)
     ORDER BY c.fecha DESC, c.hora_inicio DESC
     LIMIT 100"
);
$stmt->execute([':pid' => $prof_id, ':pid2' => $prof_id]);
$clases_virtuales = $stmt->fetchAll();

$clase_preselect = (int) ($_GET['clase_id'] ?? 0);

$partes    = explode(' ', $_SESSION['nombre']);
$iniciales = strtoupper(substr($partes[0], 0, 1) . substr($partes[1] ?? '', 0, 1));

require_once '../views/profesor/importar_teams_view.php';
