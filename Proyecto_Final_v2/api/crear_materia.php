<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_auth(['admin', 'secretaria', 'profesor']);

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['message' => 'Método no permitido']);
    exit;
}

$body      = json_decode(file_get_contents('php://input'), true) ?? [];
$nombre    = trim($body['nombre']    ?? '');
$codigo    = trim($body['codigo']    ?? '');
$curso     = trim($body['curso']     ?? '');
$modalidad = in_array($body['modalidad'] ?? '', ['presencial','virtual']) ? $body['modalidad'] : 'presencial';
$horarios  = $body['horarios'] ?? [];   // [{dia:2, hora_inicio:"18:30", hora_fin:"22:30"}, ...]

$pid_raw     = $body['profesor_id'] ?? 0;
$profesor_id = ($pid_raw === 'self')
    ? (int) $_SESSION['usuario_id']
    : ((int) $pid_raw ?: null);

if (!$nombre || !$curso) {
    http_response_code(400);
    echo json_encode(['message' => 'Nombre y curso son obligatorios']);
    exit;
}

if (!$codigo) {
    $palabras = preg_split('/\s+/', strtoupper($nombre));
    $codigo   = implode('', array_map(fn($p) => substr($p, 0, 3), $palabras));
}

try {
    $pdo = getPDO();
    $pdo->beginTransaction();

    $stmt = $pdo->prepare(
        'INSERT INTO materias (nombre, codigo, curso, modalidad, profesor_id)
         VALUES (?, ?, ?, ?, ?)'
    );
    $stmt->execute([$nombre, $codigo, $curso, $modalidad, $profesor_id]);
    $id = (int) $pdo->lastInsertId();

    // Inserta horarios semanales
    $dias_nombres = ['','Lun','Mar','Mié','Jue','Vie','Sáb','Dom'];
    $horario_txt  = [];
    if (!empty($horarios) && is_array($horarios)) {
        $ins_h = $pdo->prepare(
            'INSERT IGNORE INTO materia_horarios (materia_id, dia_semana, hora_inicio, hora_fin)
             VALUES (?, ?, ?, ?)'
        );
        foreach ($horarios as $h) {
            $dia  = (int) ($h['dia']        ?? 0);
            $hi   = $h['hora_inicio'] ?? '';
            $hf   = $h['hora_fin']    ?? '';
            if ($dia >= 1 && $dia <= 7 && $hi && $hf) {
                $ins_h->execute([$id, $dia, $hi, $hf]);
                $horario_txt[] = $dias_nombres[$dia];
            }
        }
    }

    $pdo->commit();

    $profesor = '—';
    if ($profesor_id) {
        $p = $pdo->prepare('SELECT CONCAT(nombre," ",apellido) FROM usuarios WHERE id = ?');
        $p->execute([$profesor_id]);
        $profesor = $p->fetchColumn() ?: '—';
    }

    $hora_ref = !empty($horarios[0]) ? substr($horarios[0]['hora_inicio'],0,5).' - '.substr($horarios[0]['hora_fin'],0,5) : '—';
} catch (PDOException $e) {
    $pdo->rollBack();
    if ($e->getCode() === '23000') {
        http_response_code(409);
        echo json_encode(['message' => 'El código "'.$codigo.'" ya está en uso']);
        exit;
    }
    http_response_code(500);
    echo json_encode(['message' => 'Error de base de datos']);
    exit;
}

echo json_encode([
    'ok'      => true,
    'materia' => [
        'id'          => $id,
        'nombre'      => $nombre,
        'codigo'      => $codigo,
        'curso'       => $curso,
        'modalidad'   => $modalidad,
        'profesor'    => $profesor,
        'dias'        => implode(', ', $horario_txt) ?: '—',
        'hora'        => $hora_ref,
    ],
]);
