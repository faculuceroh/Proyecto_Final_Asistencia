<?php
// api/verificar_rango.php
require_once __DIR__ . '/../includes/auth.php';
require_auth(['alumno']);

header('Content-Type: application/json');

// Incluimos las coordenadas fijas y la función de distancia
include __DIR__ . '/../config/config_geo.php';

// Capturamos el cuerpo de la petición (JSON crudo enviado por JS)
$json_recibido = file_get_contents('php://input');
$data = json_decode($json_recibido, true);

// Extraemos lat y lon de forma segura
$lat_usuario = isset($data['lat']) ? floatval($data['lat']) : 0;
$lon_usuario = isset($data['lon']) ? floatval($data['lon']) : 0;

// Si no llegaron datos válidos, rechazamos de una
if ($lat_usuario === 0.0 || $lon_usuario === 0.0) {
    echo json_encode([
        "status" => "error",
        "habilitar_camara" => false,
        "message" => "No se recibieron coordenadas válidas del GPS."
    ]);
    exit;
}

// Calculamos los metros reales de distancia
$distancia_real = calcularDistancia(UTN_LATITUD, UTN_LONGITUD, $lat_usuario, $lon_usuario);

// Evaluamos la condición de los 200 metros
if ($distancia_real <= RANGO_MAXIMO_METROS) {
    echo json_encode([
        "status" => "success",
        "habilitar_camara" => true,
        "message" => "Ubicación correcta."
    ]);
} else {
    echo json_encode([
        "status" => "denied",
        "habilitar_camara" => false,
        "message" => "Estás fuera del rango permitido. Distancia actual: " . round($distancia_real) . " metros."
    ]);
}