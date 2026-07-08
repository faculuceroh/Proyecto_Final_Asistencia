<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_auth(['profesor', 'admin']);

header('Content-Type: application/json; charset=utf-8');

$clase_id = (int)($_GET['clase_id'] ?? 0);
$prof_id  = $_SESSION['usuario_id'];
if (!$clase_id) {
    http_response_code(400); echo json_encode(['message' => 'clase_id requerido']); exit;
}

$pdo = getPDO();

// Verificar que la clase pertenece a este profesor
$stmt = $pdo->prepare(
    'SELECT c.id FROM clases c
     JOIN materias m ON m.id = c.materia_id
     WHERE c.id = ? AND (m.profesor_id = ? OR m.profesor_2_id = ?)
     LIMIT 1'
);
$stmt->execute([$clase_id, $prof_id, $prof_id]);
if (!$stmt->fetch()) {
    http_response_code(403); echo json_encode(['message' => 'Clase no encontrada o no te pertenece']); exit;
}

// Sesión activa para esta clase
$stmt = $pdo->prepare(
    'SELECT qs.id, qs.tipo, qs.expira_en, qs.activado_en, qs.aula_id,
            a.nombre AS aula_nombre, a.token AS aula_token
     FROM qr_sesiones qs
     JOIN aulas a ON a.id = qs.aula_id
     WHERE qs.clase_id = ? AND qs.activo = 1
     LIMIT 1'
);
$stmt->execute([$clase_id]);
$sesion = $stmt->fetch();

// Expiración lazy: si salida ya venció, cerrarla y finalizar clase
if ($sesion && $sesion['tipo'] === 'salida' && $sesion['expira_en'] && strtotime($sesion['expira_en']) < time()) {
    $pdo->prepare('UPDATE qr_sesiones SET activo = 0 WHERE id = ?')->execute([$sesion['id']]);
    $pdo->prepare('UPDATE clases SET estado = "finalizada" WHERE id = ?')->execute([$clase_id]);
    $sesion = null;
    $finalizada = true;
} else {
    $finalizada = false;
}

// Verificar estado actual de la clase
$stmt = $pdo->prepare('SELECT estado FROM clases WHERE id = ? LIMIT 1');
$stmt->execute([$clase_id]);
$clase = $stmt->fetch();

// Contar presentes
$stmt = $pdo->prepare(
    "SELECT COUNT(*) FROM asistencias WHERE clase_id = ? AND estado IN ('presente','tardanza')"
);
$stmt->execute([$clase_id]);
$presentes = (int)$stmt->fetchColumn();

$stmt = $pdo->prepare(
    'SELECT COUNT(*) FROM inscripciones
     WHERE materia_id = (SELECT materia_id FROM clases WHERE id = ? LIMIT 1)'
);
$stmt->execute([$clase_id]);
$total = (int)$stmt->fetchColumn();

echo json_encode([
    'ok'          => true,
    'activo'      => $sesion !== null,
    'tipo'        => $sesion ? $sesion['tipo'] : null,
    'aula_id'     => $sesion ? (int)$sesion['aula_id'] : null,
    'aula_nombre' => $sesion ? $sesion['aula_nombre'] : null,
    'aula_token'  => $sesion ? $sesion['aula_token']  : null,
    'expira_en'   => $sesion ? $sesion['expira_en']   : null,
    'expira_en_iso' => ($sesion && $sesion['expira_en']) ? gmdate('Y-m-d\TH:i:s\Z', strtotime($sesion['expira_en'])) : null,
    'presentes'   => $presentes,
    'total'       => $total,
    'clase_estado'=> $clase['estado'] ?? null,
    'finalizada'  => $finalizada,
]);
