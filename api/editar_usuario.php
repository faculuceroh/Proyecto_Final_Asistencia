<?php
/*
 * api/editar_usuario.php — Corrige los datos de un usuario existente (POST)
 * Body JSON: { id, nombre, apellido, legajo, email?, curso? }
 *
 * No modifica password, rol ni activo — es solo para arreglar errores de
 * tipeo (nombre, apellido, legajo, email, curso) en un usuario ya creado.
 */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../models/Usuario.php';
require_auth(['admin', 'secretaria']);

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['message' => 'Método no permitido']);
    exit;
}

$body     = json_decode(file_get_contents('php://input'), true) ?? [];
$id       = (int) ($body['id'] ?? 0);
$nombre   = trim($body['nombre']   ?? '');
$apellido = trim($body['apellido'] ?? '');
$legajo   = trim($body['legajo']   ?? '');
$email    = trim($body['email']    ?? '') ?: null;
$curso    = trim($body['curso']    ?? '') ?: null;

if (!$id || !$nombre || !$apellido || !$legajo) {
    http_response_code(400);
    echo json_encode(['message' => 'Nombre, apellido y legajo son obligatorios']);
    exit;
}

$pdo = getPDO();

$stmt = $pdo->prepare('SELECT rol, curso FROM usuarios WHERE id = ? LIMIT 1');
$stmt->execute([$id]);
$usuario = $stmt->fetch();

if (!$usuario) {
    http_response_code(404);
    echo json_encode(['message' => 'Usuario no encontrado']);
    exit;
}

if ($usuario['rol'] === 'alumno' && !$curso) {
    http_response_code(400);
    echo json_encode(['message' => 'El curso es obligatorio para alumnos']);
    exit;
}
// El curso solo aplica a alumnos; para el resto de los roles no se guarda.
if ($usuario['rol'] !== 'alumno') {
    $curso = null;
}

try {
    Usuario::update($id, $legajo, $nombre, $apellido, $email, $curso);
} catch (PDOException $e) {
    if ($e->getCode() === '23000') {
        http_response_code(409);
        $msg = str_contains($e->getMessage(), 'email')
            ? 'Ese email ya está en uso por otro usuario'
            : 'El legajo ' . $legajo . ' ya está en uso por otro usuario';
        echo json_encode(['message' => $msg]);
        exit;
    }
    http_response_code(500);
    echo json_encode(['message' => 'Error de base de datos']);
    exit;
}

echo json_encode(['ok' => true]);
