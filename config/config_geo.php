<?php
// config/config_geo.php

// UTN Facultad Regional Haedo — París 532, Haedo, Buenos Aires
define('UTN_LATITUD', -34.6404287);
define('UTN_LONGITUD', -58.6015669);

// Rango máximo permitido (200 metros)
define('RANGO_MAXIMO_METROS', 200.0);

if (!function_exists('calcularDistancia')) {
    // Fórmula de Haversine: distancia en metros entre dos coordenadas
    function calcularDistancia($lat1, $lon1, $lat2, $lon2) {
        $radio_tierra = 6371000;
        $dlat = deg2rad($lat2 - $lat1);
        $dlon = deg2rad($lon2 - $lon1);
        $a = sin($dlat/2) * sin($dlat/2) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dlon/2) * sin($dlon/2);
        $c = 2 * atan2(sqrt($a), sqrt(1-$a));
        return $radio_tierra * $c;
    }
}