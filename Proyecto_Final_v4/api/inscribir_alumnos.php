<?php
/*
 * api/inscribir_alumnos.php
 *
 * POST multipart:
 *   materia_id  (int)
 *   archivo     (file, CSV/XLSX con columna Legajo — opcional)
 *
 * POST JSON:
 *   { materia_id: N, legajos: ["20001","20002",...] }
 *
 * Devuelve { ok, inscritos, no_encontrados, ya_inscritos }
 */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_auth(['admin', 'secretaria']);

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['message' => 'Método no permitido']);
    exit;
}

$pdo = getPDO();

// ── Detectar modo: JSON (legajo manual) vs multipart (archivo) ─
$es_archivo = !empty($_FILES['archivo']['tmp_name']);

if ($es_archivo) {
    $materia_id = (int) ($_POST['materia_id'] ?? 0);
} else {
    $body       = json_decode(file_get_contents('php://input'), true) ?? [];
    $materia_id = (int) ($body['materia_id'] ?? 0);
    $legajos_manual = array_filter(array_map('trim', (array)($body['legajos'] ?? [])));
}

if (!$materia_id) {
    http_response_code(400);
    echo json_encode(['message' => 'materia_id requerido']);
    exit;
}

// Verifica que la materia exista
$stmt = $pdo->prepare('SELECT id FROM materias WHERE id = ? LIMIT 1');
$stmt->execute([$materia_id]);
if (!$stmt->fetch()) {
    http_response_code(404);
    echo json_encode(['message' => 'Materia no encontrada']);
    exit;
}

// ── Extraer legajos ───────────────────────────────────────────
$legajos = [];

if ($es_archivo) {
    $tmp  = $_FILES['archivo']['tmp_name'];
    $ext  = strtolower(pathinfo($_FILES['archivo']['name'], PATHINFO_EXTENSION));

    if ($ext === 'csv') {
        if (($fh = fopen($tmp, 'r')) !== false) {
            $primera = true;
            while (($row = fgetcsv($fh, 0, ';')) !== false) {
                if (count($row) < 1) continue;
                // Auto-detectar coma si fallo con punto y coma
                if (count($row) === 1) $row = str_getcsv($row[0], ',');
                $val = trim($row[0]);
                if ($primera && !is_numeric($val)) { $primera = false; continue; } // saltar header
                $primera = false;
                if ($val !== '') $legajos[] = $val;
            }
            fclose($fh);
        }
    } elseif ($ext === 'xlsx') {
        $zip = new ZipArchive();
        if ($zip->open($tmp) === true) {
            $xml = $zip->getFromName('xl/worksheets/sheet1.xml');
            // Tabla de strings compartidos
            $sst_raw = $zip->getFromName('xl/sharedStrings.xml');
            $zip->close();
            $sst = [];
            if ($sst_raw) {
                $sst_xml = simplexml_load_string($sst_raw);
                foreach ($sst_xml->si as $si) {
                    $sst[] = (string)($si->t ?? implode('', (array)$si->r->t ?? []));
                }
            }
            $sheet = simplexml_load_string($xml);
            $primera = true;
            foreach ($sheet->sheetData->row as $row) {
                $celdas = [];
                foreach ($row->c as $c) {
                    $tipo = (string)($c['t'] ?? '');
                    $v    = (string)($c->v ?? '');
                    $celdas[] = ($tipo === 's') ? ($sst[(int)$v] ?? '') : $v;
                }
                if (empty($celdas)) continue;
                $val = trim($celdas[0]);
                if ($primera && !is_numeric($val)) { $primera = false; continue; }
                $primera = false;
                if ($val !== '') $legajos[] = $val;
            }
        }
    } else {
        http_response_code(422);
        echo json_encode(['message' => 'Formato no soportado. Usá CSV o XLSX.']);
        exit;
    }
} else {
    $legajos = $legajos_manual;
}

if (empty($legajos)) {
    http_response_code(400);
    echo json_encode(['message' => 'No se encontraron legajos en el archivo o la lista está vacía']);
    exit;
}

// ── Inscribir ─────────────────────────────────────────────────
$inscritos       = 0;
$no_encontrados  = [];
$ya_inscritos    = 0;

$find = $pdo->prepare(
    "SELECT id FROM usuarios WHERE legajo = ? AND rol = 'alumno' LIMIT 1"
);
$ins = $pdo->prepare(
    'INSERT IGNORE INTO inscripciones (alumno_id, materia_id) VALUES (?, ?)'
);

foreach ($legajos as $legajo) {
    $find->execute([$legajo]);
    $alumno_id = $find->fetchColumn();
    if (!$alumno_id) {
        $no_encontrados[] = $legajo;
        continue;
    }
    $ins->execute([$alumno_id, $materia_id]);
    if ($ins->rowCount() > 0) {
        $inscritos++;
    } else {
        $ya_inscritos++;
    }
}

echo json_encode([
    'ok'             => true,
    'inscritos'      => $inscritos,
    'ya_inscritos'   => $ya_inscritos,
    'no_encontrados' => $no_encontrados,
]);
