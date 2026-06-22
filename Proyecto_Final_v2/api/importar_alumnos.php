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

if (empty($_FILES['archivo']) || $_FILES['archivo']['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(['message' => 'No se recibió ningún archivo válido']);
    exit;
}

$archivo  = $_FILES['archivo'];
$ext      = strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION));

if (!in_array($ext, ['csv', 'xlsx'], true)) {
    http_response_code(400);
    echo json_encode(['message' => 'Solo se aceptan archivos .csv o .xlsx']);
    exit;
}

// ── Parsear el archivo ────────────────────────────────────────
$filas = [];

if ($ext === 'csv') {
    $filas = parsear_csv($archivo['tmp_name']);
} else {
    $filas = parsear_xlsx($archivo['tmp_name']);
}

if (empty($filas)) {
    http_response_code(422);
    echo json_encode(['message' => 'El archivo está vacío o no tiene el formato correcto']);
    exit;
}


// ── Insertar en la base de datos ──────────────────────────────
$pdo     = getPDO();
$stmt    = $pdo->prepare(
    'INSERT IGNORE INTO usuarios (legajo, nombre, apellido, rol, curso, email, password)
     VALUES (?, ?, ?, "alumno", ?, ?, ?)'
);

$creados  = 0;
$errores  = [];

// Detecta si la primera fila es encabezado (contiene texto no numérico en "legajo")
$primera = $filas[0];
$inicio  = (isset($primera[2]) && !is_numeric(trim($primera[2]))) ? 1 : 0;

for ($i = $inicio; $i < count($filas); $i++) {
    $fila = $filas[$i];

    // Formato esperado: Nombre | Apellido | Legajo | Curso | Email
    $nombre   = trim($fila[0] ?? '');
    $apellido = trim($fila[1] ?? '');
    $legajo   = trim($fila[2] ?? '');
    $curso    = trim($fila[3] ?? '');
    $email    = trim($fila[4] ?? '') ?: null;

    if (!$nombre || !$apellido || !$legajo) {
        $errores[] = 'Fila ' . ($i + 1) . ': faltan datos obligatorios (nombre, apellido, legajo)';
        continue;
    }

    if ($email && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errores[] = 'Fila ' . ($i + 1) . ' (legajo ' . $legajo . '): email inválido — ' . $email;
        continue;
    }

    // Contraseña inicial = legajo
    $password_hash = password_hash($legajo, PASSWORD_BCRYPT);

    try {
        $stmt->execute([$legajo, $nombre, $apellido, $curso ?: null, $email, $password_hash]);
        if ($stmt->rowCount() > 0) {
            $creados++;
        }
    } catch (PDOException $e) {
        $errores[] = 'Fila ' . ($i + 1) . ' (legajo ' . $legajo . '): error al insertar';
    }
}

echo json_encode([
    'ok'      => true,
    'creados' => $creados,
    'errores' => $errores,
]);

// ── Funciones de parseo ───────────────────────────────────────

function parsear_csv(string $ruta): array
{
    $filas = [];
    if (($h = fopen($ruta, 'r')) === false) return $filas;

    // Elimina BOM UTF-8 si existe
    $bom = fread($h, 3);
    if ($bom !== "\xEF\xBB\xBF") rewind($h);

    // Detecta delimitador (coma, punto y coma o tabulación)
    $primera_linea = fgets($h);
    rewind($h);
    if ($bom === "\xEF\xBB\xBF") fread($h, 3); // saltar BOM de nuevo
    $delimitador = ';';
    if (substr_count($primera_linea, ',') > substr_count($primera_linea, ';')) $delimitador = ',';
    if (substr_count($primera_linea, "\t") > substr_count($primera_linea, $delimitador)) $delimitador = "\t";

    while (($fila = fgetcsv($h, 0, $delimitador)) !== false) {
        if (array_filter($fila)) {
            $filas[] = $fila;
        }
    }
    fclose($h);
    return $filas;
}

function col_idx(string $col): int
{
    $idx = 0;
    foreach (str_split($col) as $c) {
        $idx = $idx * 26 + (ord($c) - ord('A') + 1);
    }
    return $idx - 1;
}

function parsear_xlsx(string $ruta): array
{
    $filas = [];

    $zip = new ZipArchive();
    if ($zip->open($ruta) !== true) return $filas;

    // Shared strings
    $strings = [];
    $shared  = $zip->getFromName('xl/sharedStrings.xml');
    if ($shared) {
        // Elimina namespaces para que SimpleXML encuentre los elementos
        $shared = preg_replace('/xmlns[^=]*="[^"]*"/i', '', $shared);
        $xml    = simplexml_load_string($shared);
        foreach ($xml->si as $si) {
            if (isset($si->t)) {
                $strings[] = (string) $si->t;
            } else {
                $txt = '';
                foreach ($si->r as $r) $txt .= (string) $r->t;
                $strings[] = $txt;
            }
        }
    }

    $hoja = $zip->getFromName('xl/worksheets/sheet1.xml');
    $zip->close();
    if (!$hoja) return $filas;

    $hoja = preg_replace('/xmlns[^=]*="[^"]*"/i', '', $hoja);
    $xml  = simplexml_load_string($hoja);
    foreach ($xml->sheetData->row as $row) {
        $fila = [];
        foreach ($row->c as $c) {
            // Índice de columna desde referencia "A1", "B2", etc.
            preg_match('/^([A-Z]+)/', (string)($c['r'] ?? 'A'), $m);
            $col  = col_idx($m[1] ?? 'A');
            $tipo = (string)($c['t'] ?? '');

            if ($tipo === 's') {
                // Shared string
                $valor = $strings[(int)((string)($c->v ?? ''))] ?? '';
            } elseif ($tipo === 'inlineStr') {
                // Inline string: <is><t>valor</t></is>
                $valor = (string)($c->is->t ?? '');
            } elseif ($tipo === 'str') {
                // Fórmula que produce string
                $valor = (string)($c->v ?? '');
            } else {
                // Número u otro valor directo
                $valor = (string)($c->v ?? '');
            }

            while (count($fila) < $col) $fila[] = '';
            $fila[] = $valor;
        }
        if (array_filter($fila)) {
            $filas[] = $fila;
        }
    }

    return $filas;
}
