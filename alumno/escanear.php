<?php
require_once '../includes/auth.php';
require_auth(['alumno']);

$partes    = explode(' ', $_SESSION['nombre']);
$iniciales = strtoupper(substr($partes[0],0,1).substr($partes[1]??'',0,1));

require_once '../views/alumno/escanear_view.php';


