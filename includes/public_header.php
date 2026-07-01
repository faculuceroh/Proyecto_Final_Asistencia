<?php
/**
 * Header público compartido (landing: index / quiénes somos / contacto).
 *
 * Definí estas variables ANTES de incluir este archivo:
 *   $page_title  string  Título de la pestaña del navegador.
 *   $nav_active  string  Pestaña activa: 'inicio' | 'quienes' | 'contacto'.
 */
$page_title = $page_title ?? 'Asistencia QR';
$nav_active = $nav_active ?? '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?php echo htmlspecialchars($page_title); ?></title>

  <!-- Google Fonts: Plus Jakarta Sans -->
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet" />

  <!-- Íconos -->
  <link rel="stylesheet" href="assets/vendor/fontawesome/css/all.min.css" />

  <!-- Estilos -->
  <link rel="stylesheet" href="assets/css/main.css" />
  <link rel="stylesheet" href="assets/css/auth.css" />
</head>
<body>
  <!-- ===== Header ===== -->
  <header class="site-header">
    <div class="container">
      <a class="site-brand" href="index.php" aria-label="Volver al inicio">
        <img src="assets/img/scan-utn.png" alt="Logo de Asistencia QR - UTN Haedo" width="160" height="64" />
      </a>
      <nav class="site-nav" aria-label="Navegación principal">
        <a href="index.php"<?php echo $nav_active === 'inicio' ? ' class="is-active"' : ''; ?>>Inicio</a>
        <a href="quienes-somos.php"<?php echo $nav_active === 'quienes' ? ' class="is-active"' : ''; ?>>Quiénes somos</a>
        <a href="contacto.php"<?php echo $nav_active === 'contacto' ? ' class="is-active"' : ''; ?>>Contacto</a>
      </nav>
    </div>
  </header>
