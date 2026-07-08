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

$body     = json_decode(file_get_contents('php://input'), true) ?? [];
$tipo     = trim($body['tipo']     ?? '');
$nombre   = trim($body['nombre']   ?? '');
$apellido = trim($body['apellido'] ?? '');
$legajo   = trim($body['legajo']   ?? '');
$curso    = trim($body['curso']    ?? '');
$email    = trim($body['email']    ?? '') ?: null;

// Validaciones. Secretaría no puede dar de alta administradores.
$roles_validos = $_SESSION['rol'] === 'admin'
    ? ['alumno', 'profesor', 'secretaria', 'admin']
    : ['alumno', 'profesor', 'secretaria'];
if (!in_array($tipo, $roles_validos, true)) {
    http_response_code(400);
    echo json_encode(['message' => 'Tipo de usuario inválido']);
    exit;
}
if (!$nombre || !$apellido || !$legajo) {
    http_response_code(400);
    echo json_encode(['message' => 'Nombre, apellido y legajo son obligatorios']);
    exit;
}
if ($tipo === 'alumno' && !$curso) {
    http_response_code(400);
    echo json_encode(['message' => 'El curso es obligatorio para alumnos']);
    exit;
}

// La contraseña inicial es el mismo legajo (el usuario la cambia después)
$password_hash = password_hash($legajo, PASSWORD_BCRYPT);

try {
    $pdo  = getPDO();
    $stmt = $pdo->prepare(
        'INSERT INTO usuarios (legajo, nombre, apellido, email, password, rol, curso)
         VALUES (?, ?, ?, ?, ?, ?, ?)'
    );
    $stmt->execute([
        $legajo,
        $nombre,
        $apellido,
        $email,
        $password_hash,
        $tipo,
        $tipo === 'alumno' ? $curso : null,
    ]);
    $id = (int) $pdo->lastInsertId();
} catch (PDOException $e) {
    // Legajo duplicado
    if ($e->getCode() === '23000') {
        http_response_code(409);
        echo json_encode(['message' => 'El legajo ' . $legajo . ' ya existe']);
        exit;
    }
    http_response_code(500);
    echo json_encode(['message' => 'Error de base de datos']);
    exit;
}

echo json_encode([
    'ok' => true,
    'id' => $id,
    'message' => 'Usuario creado. Contraseña inicial: ' . $legajo,
]);
