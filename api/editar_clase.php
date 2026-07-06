<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_auth(['secretaria', 'admin']);

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['message' => 'Método no permitido']);
    exit;
}

$body         = json_decode(file_get_contents('php://input'), true) ?? [];
$clase_id     = (int) ($body['clase_id'] ?? 0);
$fecha        = trim($body['fecha'] ?? '');
$hora_inicio  = trim($body['hora_inicio'] ?? '');
$duracion_min = (int) ($body['duracion_min'] ?? 90);
$aula         = trim($body['aula'] ?? '');
$modalidad    = in_array($body['modalidad'] ?? '', ['presencial','virtual','hibrida']) ? $body['modalidad'] : 'presencial';
$estado       = in_array($body['estado'] ?? '', ['pendiente','en_curso','finalizada','suspendida']) ? $body['estado'] : 'pendiente';

if (!$clase_id || !$fecha || !$hora_inicio || !$duracion_min) {
    http_response_code(400);
    echo json_encode(['message' => 'ID de clase, fecha, hora de inicio y duración son obligatorios.']);
    exit;
}

// Convertir aula a NULL si es virtual o está vacía
if ($modalidad === 'virtual' || !$aula) {
    $aula = null;
}

$pdo = getPDO();

// Verificar que la clase existe
$stmt = $pdo->prepare("SELECT id FROM clases WHERE id = ? LIMIT 1");
$stmt->execute([$clase_id]);
if (!$stmt->fetch()) {
    http_response_code(404);
    echo json_encode(['message' => 'Clase no encontrada.']);
    exit;
}

try {
    $stmt = $pdo->prepare("
        UPDATE clases
        SET fecha = ?, hora_inicio = ?, duracion_min = ?, aula = ?, modalidad = ?, estado = ?
        WHERE id = ?
    ");
    $stmt->execute([$fecha, $hora_inicio, $duracion_min, $aula, $modalidad, $estado, $clase_id]);

    // Si el estado cambia a finalizada, y no se registraron asistencias, marcar ausentes a todos?
    // En el flujo normal, la finalización se hace desde la toma de asistencia. Aquí solo se edita metadatos.
    
    // Obtener la clase actualizada con sus estadísticas
    $stmt = $pdo->prepare("
        SELECT c.id, c.fecha, c.hora_inicio, c.duracion_min, c.aula, c.modalidad, c.estado,
               m.nombre AS materia, m.curso,
               COALESCE(CONCAT(u.nombre,' ',u.apellido),'—') AS profesor,
               (SELECT COUNT(*) FROM asistencias WHERE clase_id = c.id AND estado IN ('presente','tardanza')) AS presentes,
               (SELECT COUNT(*) FROM asistencias WHERE clase_id = c.id AND estado = 'ausente') AS ausentes,
               (SELECT ROUND(SUM(estado IN ('presente','tardanza'))/NULLIF(COUNT(*),0)*100,1) FROM asistencias WHERE clase_id = c.id) AS pct
        FROM clases c
        JOIN materias m ON m.id = c.materia_id
        LEFT JOIN usuarios u ON u.id = m.profesor_id
        WHERE c.id = ?
        LIMIT 1
    ");
    $stmt->execute([$clase_id]);
    $updated_clase = $stmt->fetch(PDO::FETCH_ASSOC);

    echo json_encode([
        'ok' => true,
        'clase' => $updated_clase
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['message' => 'Error al actualizar la clase en la base de datos.']);
}
