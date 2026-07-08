<?php
/*
 * api/actualizar_foto.php — Sube/reemplaza la foto de perfil del usuario logueado (POST multipart)
 * Campo esperado: foto (archivo)
 *
 * Valida formato (JPEG/PNG/WebP) y peso máximo, y la recorta/redimensiona
 * a un cuadrado fijo de 300x300 antes de guardarla en
 * assets/uploads/perfiles/. Solo se guarda el nombre de archivo en la BD.
 */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../models/Usuario.php';
require_auth(['alumno', 'profesor', 'secretaria']);

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); echo json_encode(['message' => 'Método no permitido']); exit;
}

const MAX_BYTES = 2 * 1024 * 1024; // 2 MB
const TAMANO_AVATAR = 300;         // px, cuadrado
$TIPOS_PERMITIDOS = [
    IMAGETYPE_JPEG => 'jpg',
    IMAGETYPE_PNG  => 'png',
    IMAGETYPE_WEBP => 'webp',
];

if (empty($_FILES['foto']) || $_FILES['foto']['error'] === UPLOAD_ERR_NO_FILE) {
    http_response_code(400);
    echo json_encode(['message' => 'No se recibió ninguna foto']);
    exit;
}

$archivo = $_FILES['foto'];

if ($archivo['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(['message' => 'Error al subir el archivo']);
    exit;
}

if ($archivo['size'] > MAX_BYTES) {
    http_response_code(422);
    echo json_encode(['message' => 'La foto pesa demasiado. Máximo 2 MB.']);
    exit;
}

// Valida el contenido real del archivo (no solo la extensión que mandó el navegador)
$info = @getimagesize($archivo['tmp_name']);
if ($info === false || !isset($TIPOS_PERMITIDOS[$info[2]])) {
    http_response_code(422);
    echo json_encode(['message' => 'Formato no válido. Usá JPEG, PNG o WebP.']);
    exit;
}

$tipo       = $info[2];
$usuario_id = $_SESSION['usuario_id'];
$dirDestino = __DIR__ . '/../assets/uploads/perfiles/';

if (!is_dir($dirDestino)) {
    mkdir($dirDestino, 0755, true);
}

if (extension_loaded('gd')) {
    // Recorte centrado a cuadrado + resize a tamaño fijo (avatar consistente y liviano)
    $src = match ($tipo) {
        IMAGETYPE_JPEG => imagecreatefromjpeg($archivo['tmp_name']),
        IMAGETYPE_PNG  => imagecreatefrompng($archivo['tmp_name']),
        IMAGETYPE_WEBP => imagecreatefromwebp($archivo['tmp_name']),
    };

    if (!$src) {
        http_response_code(422);
        echo json_encode(['message' => 'No se pudo procesar la imagen']);
        exit;
    }

    $srcW = imagesx($src);
    $srcH = imagesy($src);
    $srcSize = min($srcW, $srcH);
    $srcX = (int) (($srcW - $srcSize) / 2);
    $srcY = (int) (($srcH - $srcSize) / 2);

    $dst = imagecreatetruecolor(TAMANO_AVATAR, TAMANO_AVATAR);
    imagefill($dst, 0, 0, imagecolorallocate($dst, 255, 255, 255)); // fondo blanco (aplana transparencia)
    imagecopyresampled($dst, $src, 0, 0, $srcX, $srcY, TAMANO_AVATAR, TAMANO_AVATAR, $srcSize, $srcSize);
    imagedestroy($src);

    $nombreNuevo = 'u' . $usuario_id . '_' . time() . '.jpg';
    $rutaDestino = $dirDestino . $nombreNuevo;

    if (!imagejpeg($dst, $rutaDestino, 85)) {
        imagedestroy($dst);
        http_response_code(500);
        echo json_encode(['message' => 'No se pudo guardar la foto']);
        exit;
    }
    imagedestroy($dst);
} else {
    // Sin la extensión GD (no habilitada en este XAMPP): se guarda el archivo
    // tal cual llegó, sin recorte ni reencodeo. El avatar se ve igual de bien
    // gracias al CSS (object-fit: cover); solo se pierde la optimización de
    // peso y el recorte cuadrado automático. La extensión real ya se validó
    // arriba con getimagesize(), no se confía en la que mandó el navegador.
    $nombreNuevo = 'u' . $usuario_id . '_' . time() . '.' . $TIPOS_PERMITIDOS[$tipo];
    $rutaDestino = $dirDestino . $nombreNuevo;

    if (!move_uploaded_file($archivo['tmp_name'], $rutaDestino)) {
        http_response_code(500);
        echo json_encode(['message' => 'No se pudo guardar la foto']);
        exit;
    }
}

// Borra la foto anterior (si había) y guarda la nueva
$stmt = getPDO()->prepare('SELECT foto FROM usuarios WHERE id = ? LIMIT 1');
$stmt->execute([$usuario_id]);
$fotoAnterior = $stmt->fetchColumn();

Usuario::updateFoto($usuario_id, $nombreNuevo);
$_SESSION['foto'] = $nombreNuevo;

if ($fotoAnterior) {
    $rutaAnterior = $dirDestino . basename($fotoAnterior);
    if (is_file($rutaAnterior)) {
        @unlink($rutaAnterior);
    }
}

echo json_encode(['ok' => true, 'foto' => $nombreNuevo]);
