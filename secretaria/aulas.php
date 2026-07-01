<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';

require_auth(['secretaria', 'admin']);

$pdo = getPDO();

$aulas = $pdo->query(
    'SELECT id, nombre, token, created_at FROM aulas WHERE activo = 1 ORDER BY nombre'
)->fetchAll(PDO::FETCH_ASSOC);

$partes    = explode(' ', $_SESSION['nombre']);
$iniciales = strtoupper(substr($partes[0],0,1).substr($partes[1]??'',0,1));

$proto   = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host    = $_SERVER['HTTP_HOST'];
$base_qr = $proto . '://' . $host . '/asistencia/alumno/escanear.php?aula=';

// Carga la Vista
require_once '../views/secretaria/aulas_view.php';
