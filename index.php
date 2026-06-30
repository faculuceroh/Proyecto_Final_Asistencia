<?php
if (session_status() === PHP_SESSION_NONE) session_start();

// Si ya está logueado, redirigir a su dashboard
if (!empty($_SESSION['usuario_id']) && !empty($_SESSION['rol'])) {
    $destinos = [
        'alumno'     => 'alumno/dashboard.php',
        'profesor'   => 'profesor/dashboard.php',
        'secretaria' => 'secretaria/exportar.php',
        'admin'      => 'admin/dashboard.php',
    ];
    if (isset($destinos[$_SESSION['rol']])) {
        header('Location: ' . $destinos[$_SESSION['rol']]);
        exit;
    }
}

$page_title = 'Ingresar · Asistencia QR';
$nav_active = 'inicio';
require __DIR__ . '/includes/public_header.php';

// Carga la Vista
require __DIR__ . '/views/public/index_view.php';

require __DIR__ . '/includes/public_footer.php';

