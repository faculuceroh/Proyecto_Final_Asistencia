<?php
require_once '../includes/auth.php';
require_auth(['alumno']);

$partes    = explode(' ', $_SESSION['nombre']);
$iniciales = strtoupper(substr($partes[0],0,1).substr($partes[1]??'',0,1));
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" /><meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0" />
  <title>Escanear asistencia · Asistencia QR</title>
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
  <link rel="stylesheet" href="../assets/css/main.css" />
  <link rel="stylesheet" href="../assets/css/qr.css" />
</head>
<body>
  <main class="scan-screen">
    <!--
      data-registrar-url le indica a qr-scanner.js dónde hacer POST.
      Sin este atributo el JS entraría en modo demo.
    -->
    <section class="scan-card" id="scanRoot"
             data-registrar-url="../api/registrar.php">

      <header class="scan-head">
        <div class="ico"><i class="fa-solid fa-camera"></i></div>
        <h1>Registrar asistencia</h1>
        <div class="clase-meta">
          <span><i class="fa-solid fa-qrcode"></i> Escaneá el QR que muestra tu profesor</span>
        </div>
      </header>

      <!-- 1) Pedido de permiso de cámara -->
      <div class="scan-body cam-prompt" id="camPrompt">
        <div class="cam-ico"><i class="fa-solid fa-camera"></i></div>
        <p class="scan-label">Necesitamos tu cámara</p>
        <p class="scan-sub">Vamos a usar la cámara solo para leer el código QR de la clase. No se graba nada.</p>
        <button class="btn btn-success btn-block btn-lg" id="startCamBtn">
          <i class="fa-solid fa-camera"></i> Abrir cámara y escanear
        </button>
        <button class="btn btn-ghost btn-block mt-2" id="demoScanBtn">
          <i class="fa-solid fa-wand-magic-sparkles"></i> Simular escaneo (demo)
        </button>
        <a href="dashboard.php" class="btn btn-ghost btn-block mt-1" style="font-size:0.85rem">
          <i class="fa-solid fa-arrow-left"></i> Volver al inicio
        </a>
      </div>

      <!-- 2) Cámara en vivo -->
      <div class="scan-body hidden" id="camView">
        <div class="cam-stage">
          <video id="camVideo" muted playsinline></video>
          <div class="scan-frame"></div>
          <div class="scan-line"></div>
          <canvas id="camCanvas"></canvas>
        </div>
        <p class="cam-hint"><i class="fa-solid fa-magnifying-glass"></i> Apuntá al QR dentro del recuadro</p>
        <button class="btn btn-ghost btn-block" id="cancelCamBtn"><i class="fa-solid fa-xmark"></i> Cancelar</button>
      </div>

      <!-- 3) Resultado (lo llena el JS) -->
      <div class="scan-result hidden" id="scanResult"></div>
    </section>
  </main>

  <script src="https://cdnjs.cloudflare.com/ajax/libs/jsQR/1.4.0/jsQR.min.js"></script>
  <script src="../assets/js/utils.js"></script>
  <script src="../assets/js/qr-scanner.js"></script>
</body>
</html>

