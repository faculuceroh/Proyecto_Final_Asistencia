<?php
require_once '../includes/auth.php';
require_once '../models/Usuario.php';

require_auth(['secretaria', 'admin']);

$user = Usuario::findById($_SESSION['usuario_id']);

$partes    = explode(' ', $_SESSION['nombre']);
$iniciales = strtoupper(substr($partes[0],0,1).substr($partes[1]??'',0,1));

// Carga la Vista
require_once '../views/secretaria/perfil_view.php';

