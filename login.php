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
$pdo = getPDO();
try {
    $stmt = $pdo->prepare(
        'SELECT id, nombre, apellido, password, rol, activo, foto, intentos_fallidos, bloqueado_hasta
         FROM usuarios WHERE legajo = ? LIMIT 1'
    );
    $stmt->execute([$legajo]);
    $user = $stmt->fetch();
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['message' => 'Error de base de datos']);
    exit;
}

const MAX_INTENTOS      = 5;
const BLOQUEO_MINUTOS   = 15;

// Cuenta bloqueada por demasiados intentos fallidos recientes
if ($user && $user['bloqueado_hasta'] && strtotime($user['bloqueado_hasta']) > time()) {
    $restantes = (int) ceil((strtotime($user['bloqueado_hasta']) - time()) / 60);
    http_response_code(429);
    echo json_encode(['message' => "Demasiados intentos fallidos. Probá de nuevo en $restantes minuto(s)."]);
    exit;
}

// Valida credenciales (password_verify compara contra el hash bcrypt)
if (!$user || !password_verify($password, $user['password'])) {
    if ($user) {
        $intentos = $user['intentos_fallidos'] + 1;
        $bloqueo  = $intentos >= MAX_INTENTOS
            ? date('Y-m-d H:i:s', time() + BLOQUEO_MINUTOS * 60)
            : null;
        $pdo->prepare('UPDATE usuarios SET intentos_fallidos = ?, bloqueado_hasta = ? WHERE id = ?')
            ->execute([$bloqueo ? 0 : $intentos, $bloqueo, $user['id']]);
    }
    http_response_code(401);
    echo json_encode(['message' => 'Legajo o contraseña incorrectos']);
    exit;
}

// Login correcto: resetea el contador de intentos fallidos
$pdo->prepare('UPDATE usuarios SET intentos_fallidos = 0, bloqueado_hasta = NULL WHERE id = ?')
    ->execute([$user['id']]);

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

// Genera un ID de sesión nuevo tras autenticar (evita session fixation)
session_regenerate_id(true);

// Guarda los datos del usuario en la sesión PHP
$_SESSION['usuario_id'] = (int) $user['id'];
$_SESSION['nombre']     = $user['nombre'] . ' ' . $user['apellido'];
$_SESSION['rol']        = $user['rol'];
$_SESSION['foto']       = $user['foto'];

echo json_encode([
    'ok'     => true,
    'dest'   => $destinos[$user['rol']] ?? 'index.php',
    'nombre' => $user['nombre'],
    'rol'    => $user['rol'],
]);
