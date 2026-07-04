<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_auth(['admin', 'secretaria']);

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['message' => 'Método no permitido']);
    exit;
}

$body         = json_decode(file_get_contents('php://input'), true) ?? [];
$materia_id   = (int) ($body['materia_id'] ?? 0);
$nombre       = trim($body['nombre']    ?? '');
$codigo       = trim($body['codigo']    ?? '');
$curso        = trim($body['curso']     ?? '');
$modalidad    = in_array($body['modalidad'] ?? '', ['presencial','virtual','hibrida']) ? $body['modalidad'] : 'presencial';
$horarios     = $body['horarios'] ?? [];   // [{dia:2, hora_inicio:"18:30", hora_fin:"22:30"}, ...]
$fecha_inicio = trim($body['fecha_inicio'] ?? '');
$fecha_fin    = trim($body['fecha_fin']    ?? '');

$pid_raw       = $body['profesor_id'] ?? 0;
$profesor_id   = (int) $pid_raw ?: null;
$pid2_raw      = $body['profesor_2_id'] ?? 0;
$profesor_2_id = (int) $pid2_raw ?: null;

if (!$materia_id || !$nombre || !$curso) {
    http_response_code(400);
    echo json_encode(['message' => 'ID de materia, nombre y curso son obligatorios']);
    exit;
}

if ($fecha_inicio && $fecha_fin && $fecha_inicio > $fecha_fin) {
    http_response_code(400);
    echo json_encode(['message' => 'La fecha de inicio no puede ser posterior al fin']);
    exit;
}

if (!$codigo) {
    $palabras = preg_split('/\s+/', strtoupper($nombre));
    $codigo   = implode('', array_map(fn($p) => substr($p, 0, 3), $palabras));
}

$pdo = getPDO();

// Verificar que la materia exista
$stmt = $pdo->prepare('SELECT id FROM materias WHERE id = ? AND activo = 1 LIMIT 1');
$stmt->execute([$materia_id]);
if (!$stmt->fetch()) {
    http_response_code(404);
    echo json_encode(['message' => 'Materia no encontrada']);
    exit;
}

// Evitar cargar la misma materia dos veces en el mismo curso (excluyendo la materia actual)
$stmt = $pdo->prepare(
    'SELECT id FROM materias
     WHERE activo = 1 AND curso = ? AND LOWER(nombre) = LOWER(?) AND id != ?
     LIMIT 1'
);
$stmt->execute([$curso, $nombre, $materia_id]);
if ($stmt->fetch()) {
    http_response_code(409);
    echo json_encode(['message' => 'Ya existe otra materia "'.$nombre.'" en el curso "'.$curso.'"']);
    exit;
}

// Evitar conflictos de horario para los profesores asignados (excluyendo la materia actual)
$dias_nombres_check = ['','Lunes','Martes','Miércoles','Jueves','Viernes','Sábado','Domingo'];
$profesores_a_chequear = array_filter([
    'a cargo'  => $profesor_id,
    'segundo'  => $profesor_2_id,
]);
if (!empty($horarios) && is_array($horarios) && !empty($profesores_a_chequear)) {
    $stmt_conflicto = $pdo->prepare(
        'SELECT m.nombre, mh.dia_semana, mh.hora_inicio, mh.hora_fin
         FROM materia_horarios mh
         JOIN materias m ON m.id = mh.materia_id
         WHERE m.activo = 1 AND mh.dia_semana = ?
           AND (m.profesor_id = ? OR m.profesor_2_id = ?)
           AND m.id != ?
           AND mh.hora_inicio < ? AND mh.hora_fin > ?
         LIMIT 1'
     );
    foreach ($horarios as $h) {
        $dia = (int) ($h['dia'] ?? 0);
        $hi  = $h['hora_inicio'] ?? '';
        $hf  = $h['hora_fin']    ?? '';
        if (!$dia || !$hi || !$hf) continue;

        foreach ($profesores_a_chequear as $rol => $pid) {
            $stmt_conflicto->execute([$dia, $pid, $pid, $materia_id, $hf, $hi]);
            $conflicto = $stmt_conflicto->fetch();
            if ($conflicto) {
                http_response_code(409);
                echo json_encode(['message' =>
                    'El profesor '.$rol.' ya dicta "'.$conflicto['nombre'].'" los '.$dias_nombres_check[$dia].
                    ' de '.substr($conflicto['hora_inicio'],0,5).' a '.substr($conflicto['hora_fin'],0,5).'.'
                ]);
                exit;
            }
        }
    }
}

try {
    $pdo->beginTransaction();

    // Actualizar campos principales de la materia
    $stmt = $pdo->prepare(
        'UPDATE materias
         SET nombre = ?, codigo = ?, curso = ?, modalidad = ?, profesor_id = ?, profesor_2_id = ?
         WHERE id = ?'
    );
    $stmt->execute([$nombre, $codigo, $curso, $modalidad, $profesor_id, $profesor_2_id, $materia_id]);

    // Eliminar horarios anteriores
    $stmt_del = $pdo->prepare('DELETE FROM materia_horarios WHERE materia_id = ?');
    $stmt_del->execute([$materia_id]);

    // Insertar nuevos horarios
    $dias_nombres = ['','Lun','Mar','Mié','Jue','Vie','Sáb','Dom'];
    $horario_txt  = [];
    $dias_num     = [];
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
                $ins_h->execute([$materia_id, $dia, $hi, $hf]);
                $horario_txt[] = $dias_nombres[$dia];
                $dias_num[]    = $dia;
            }
        }
    }

    // Programar/reprogramar clases en el período del cuatrimestre
    $clases_generadas = 0;
    if ($fecha_inicio && $fecha_fin && !empty($horarios)) {
        // 1. Obtener clases existentes
        $stmt_c = $pdo->prepare("SELECT id, fecha, DATE_FORMAT(hora_inicio, '%H:%i') as hora_inicio, estado FROM clases WHERE materia_id = ?");
        $stmt_c->execute([$materia_id]);
        $clases_existentes = $stmt_c->fetchAll(PDO::FETCH_ASSOC);

        $existentes_map = [];
        $ids_pendientes_fuera = [];
        foreach ($clases_existentes as $ce) {
            $existentes_map[$ce['fecha']][$ce['hora_inicio']] = $ce;
            // Si la clase está pendiente y cae fuera de las nuevas fechas, se elimina
            if ($ce['estado'] === 'pendiente' && ($ce['fecha'] < $fecha_inicio || $ce['fecha'] > $fecha_fin)) {
                $ids_pendientes_fuera[] = $ce['id'];
            }
        }

        // Borrar clases pendientes fuera del nuevo rango
        if (!empty($ids_pendientes_fuera)) {
            $placeholders = implode(',', array_fill(0, count($ids_pendientes_fuera), '?'));
            $stmt_del_cls = $pdo->prepare("DELETE FROM clases WHERE id IN ($placeholders)");
            $stmt_del_cls->execute($ids_pendientes_fuera);
        }

        // 2. Generar nuevas clases
        $por_dia = [];
        foreach ($horarios as $h) {
            $por_dia[(int)$h['dia']][] = $h;
        }

        $insert_clase = $pdo->prepare(
            "INSERT IGNORE INTO clases (materia_id, fecha, hora_inicio, duracion_min, modalidad, estado)
             VALUES (?, ?, ?, ?, ?, 'pendiente')"
        );

        $current = new DateTime($fecha_inicio . ' 00:00:00');
        $limite  = new DateTime($fecha_fin   . ' 00:00:00');

        while ($current <= $limite) {
            $dow = (int)$current->format('N'); // 1=Lun..7=Dom

            if (isset($por_dia[$dow])) {
                foreach ($por_dia[$dow] as $sh) {
                    $hi_trim = substr($sh['hora_inicio'], 0, 5);
                    $fecha_f = $current->format('Y-m-d');

                    if (isset($existentes_map[$fecha_f][$hi_trim])) {
                        continue; // Ya existe clase
                    }

                    [$hh, $mm] = explode(':', $hi_trim);
                    [$fh, $fm] = explode(':', substr($sh['hora_fin'], 0, 5));
                    $duracion = ((int)$fh * 60 + (int)$fm) - ((int)$hh * 60 + (int)$mm);
                    if ($duracion <= 0) $duracion = 90;

                    $insert_clase->execute([
                        $materia_id,
                        $fecha_f,
                        $hi_trim,
                        $duracion,
                        $modalidad
                    ]);
                    if ($insert_clase->rowCount() > 0) {
                        $clases_generadas++;
                    }
                }
            }
            $current->modify('+1 day');
        }
    }

    $pdo->commit();

    $profesor = '—';
    if ($profesor_id) {
        $p = $pdo->prepare('SELECT CONCAT(nombre," ",apellido) FROM usuarios WHERE id = ?');
        $p->execute([$profesor_id]);
        $profesor = $p->fetchColumn() ?: '—';
    }

    $profesor_2 = '';
    if ($profesor_2_id) {
        $p2 = $pdo->prepare('SELECT CONCAT(nombre," ",apellido) FROM usuarios WHERE id = ?');
        $p2->execute([$profesor_2_id]);
        $profesor_2 = $p2->fetchColumn() ?: '';
    }

    $hora_ref = !empty($horarios[0]) ? substr($horarios[0]['hora_inicio'],0,5).' - '.substr($horarios[0]['hora_fin'],0,5) : '—';

} catch (PDOException $e) {
    $pdo->rollBack();
    if ($e->getCode() === '23000') {
        http_response_code(409);
        echo json_encode(['message' => 'El código "'.$codigo.'" ya está en uso por otra materia']);
        exit;
    }
    http_response_code(500);
    echo json_encode(['message' => 'Error de base de datos']);
    exit;
}

echo json_encode([
    'ok'      => true,
    'materia' => [
        'id'               => $materia_id,
        'nombre'           => $nombre,
        'codigo'           => $codigo,
        'curso'            => $curso,
        'modalidad'        => $modalidad,
        'profesor_id'      => $profesor_id,
        'profesor_2_id'    => $profesor_2_id,
        'profesor'         => $profesor,
        'profesor_2'       => $profesor_2,
        'dias'             => implode(', ', $horario_txt) ?: '—',
        'dias_num'         => implode(',', $dias_num),
        'horario'          => $hora_ref,
        'fecha_inicio'     => $fecha_inicio,
        'fecha_fin'        => $fecha_fin,
        'clases_generadas' => $clases_generadas
    ],
]);
