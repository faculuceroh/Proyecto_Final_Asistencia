<?php
// api/verificar_rango.php
header('Content-Type: application/json');

// Incluimos las coordenadas fijas de prueba
include '../config/config_geo.php';

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

// Fórmula de Haversine para calcular la distancia en metros
function calcularDistancia($lat1, $lon1, $lat2, $lon2) {
    $radio_tierra = 6371000; 
    $dlat = deg2rad($lat2 - $lat1);
    $dlon = deg2rad($lon2 - $lon1);
    $a = sin($dlat/2) * sin($dlat/2) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dlon/2) * sin($dlon/2);
    $c = 2 * atan2(sqrt($a), sqrt(1-$a));
    return $radio_tierra * $c;
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