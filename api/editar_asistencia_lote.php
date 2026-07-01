<?php
/*
 * api/editar_asistencia_lote.php — Edición masiva del estado de asistencia (POST)
 * Body JSON: { clase_id, cambios: [{ alumno_id, estado }, ...] }
 * Solo para clases del día actual que pertenezcan al profesor.
 * No permite editar hora_entrada: el profesor solo corrige el estado.
 */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_auth(['profesor', 'admin']);

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); echo json_encode(['message' => 'Método no permitido']); exit;
}

$body     = json_decode(file_get_contents('php://input'), true) ?? [];
$clase_id = (int)($body['clase_id'] ?? 0);
$cambios  = is_array($body['cambios'] ?? null) ? $body['cambios'] : [];

$estados_validos = ['presente', 'tardanza', 'ausente'];

if (!$clase_id || empty($cambios)) {
    http_response_code(400);
    echo json_encode(['message' => 'clase_id y cambios son requeridos']);
    exit;
}

$prof_id = $_SESSION['usuario_id'];
$pdo     = getPDO();

// Verificar que la clase pertenece al profesor, es de hoy y ya arrancó (en_curso o finalizada)
$stmt = $pdo->prepare(
    'SELECT c.materia_id FROM clases c
     JOIN materias m ON m.id = c.materia_id
     WHERE c.id = ? AND c.fecha = CURDATE() AND c.estado != "pendiente"
       AND (m.profesor_id = ? OR m.profesor_2_id = ?)
     LIMIT 1'
);
$stmt->execute([$clase_id, $prof_id, $prof_id]);
$clase = $stmt->fetch();
if (!$clase) {
    http_response_code(403);
    echo json_encode(['message' => 'La clase no pertenece a tus clases de hoy o todavía no arrancó']);
    exit;
}

// Alumnos inscriptos en la materia (para validar pertenencia sin una query por fila)
$stmt = $pdo->prepare('SELECT alumno_id FROM inscripciones WHERE materia_id = ?');
$stmt->execute([$clase['materia_id']]);
$inscriptos = array_flip($stmt->fetchAll(PDO::FETCH_COLUMN));

$del = $pdo->prepare('DELETE FROM asistencias WHERE alumno_id = ? AND clase_id = ?');
$ups = $pdo->prepare(
    'INSERT INTO asistencias (alumno_id, clase_id, estado)
     VALUES (?, ?, ?)
     ON DUPLICATE KEY UPDATE estado = VALUES(estado)'
);

$actualizados = 0;
$omitidos     = 0;

$pdo->beginTransaction();
try {
    foreach ($cambios as $c) {
        $alumno_id = (int)($c['alumno_id'] ?? 0);
        $estado    = $c['estado'] ?? '';

        if (!$alumno_id || !in_array($estado, $estados_validos, true) || !isset($inscriptos[$alumno_id])) {
            $omitidos++;
            continue;
        }

        if ($estado === 'ausente') {
            $del->execute([$alumno_id, $clase_id]);
        } else {
            $ups->execute([$alumno_id, $clase_id, $estado]);
        }
        $actualizados++;
    }
    $pdo->commit();
} catch (Throwable $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['message' => 'Error al guardar los cambios']);
    exit;
}

echo json_encode(['ok' => true, 'actualizados' => $actualizados, 'omitidos' => $omitidos]);
