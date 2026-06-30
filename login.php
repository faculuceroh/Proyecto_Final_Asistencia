<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/includes/db.php';

// Solo acepta POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['message' => 'Método no permitido']);
    exit;
}

// Lee el body JSON enviado por App.api()
$body     = json_decode(file_get_contents('php://input'), true) ?? [];
$legajo   = trim($body['legajo']   ?? '');
$password = trim($body['password'] ?? '');

if ($legajo === '' || $password === '') {
    http_response_code(400);
    echo json_encode(['message' => 'Completá legajo y contraseña']);
    exit;
}

// Busca el usuario en la base de datos
try {
    $stmt = getPDO()->prepare(
        'SELECT id, nombre, apellido, password, rol, activo
         FROM usuarios WHERE legajo = ? LIMIT 1'
    );
    $stmt->execute([$legajo]);
    $user = $stmt->fetch();
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['message' => 'Error de base de datos']);
    exit;
}

// Valida credenciales (password_verify compara contra el hash bcrypt)
if (!$user || !password_verify($password, $user['password'])) {
    http_response_code(401);
    echo json_encode(['message' => 'Legajo o contraseña incorrectos']);
    exit;
}

if (!(bool) $user['activo']) {
    http_response_code(403);
    echo json_encode(['message' => 'Tu cuenta está desactivada. Contactá a secretaría.']);
    exit;
}

// Panel de destino según rol
$destinos = [
    'alumno'     => 'alumno/dashboard.php',
    'profesor'   => 'profesor/dashboard.php',
    'secretaria' => 'secretaria/exportar.php',
    'admin'      => 'admin/dashboard.php',
];

// Guarda los datos del usuario en la sesión PHP
$_SESSION['usuario_id'] = (int) $user['id'];
$_SESSION['nombre']     = $user['nombre'] . ' ' . $user['apellido'];
$_SESSION['rol']        = $user['rol'];

echo json_encode([
    'ok'     => true,
    'dest'   => $destinos[$user['rol']] ?? 'index.php',
    'nombre' => $user['nombre'],
    'rol'    => $user['rol'],
]);
