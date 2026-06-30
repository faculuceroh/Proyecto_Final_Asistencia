<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Tomar asistencia · Asistencia QR</title>
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
  <link rel="stylesheet" href="../assets/css/main.css" />
  <link rel="stylesheet" href="../assets/css/qr.css" />
</head>
<body>
<div class="qr-screen">

  <header class="qr-topbar">
    <a href="dashboard.php" class="back" title="Volver"><i class="fa-solid fa-arrow-left"></i></a>
    <div class="qr-clase">
      <h1 id="qrMateria"><?= htmlspecialchars($materia) ?></h1>
      <p id="qrSub"><?= htmlspecialchars($sub) ?></p>
    </div>
    <div class="qr-topbar-right">
      <span class="badge <?= $modalidad === 'virtual' ? 'badge-muted' : 'badge-accent' ?>" id="modalidadBadge">
        <?= $modalidad === 'virtual' ? 'Virtual' : 'Presencial' ?>
      </span>
      <span class="badge badge-warning">En curso</span>
    </div>
  </header>

  <div class="qr-body">

    <!-- Panel del QR -->
    <section class="qr-panel <?= $tipo === 'salida' ? 'mode-salida' : '' ?>">
      <p class="qr-hint">
        Los alumnos escanean este código con su celular para registrar su
        <strong>entrada</strong> o <strong>salida</strong>.
      </p>

      <div class="qr-mode" role="radiogroup" aria-label="Tipo de QR">
        <label class="mode-opt opt-in">
          <input type="radio" name="qrMode" value="entrada" <?= $tipo !== 'salida' ? 'checked' : '' ?> />
          <span><i class="fa-solid fa-right-to-bracket"></i> Entrada</span>
        </label>
        <label class="mode-opt opt-out">
          <input type="radio" name="qrMode" value="salida" <?= $tipo === 'salida' ? 'checked' : '' ?> />
          <span><i class="fa-solid fa-right-from-bracket"></i> Salida</span>
        </label>
      </div>

      <!--
        data-clase-id   → ID de la clase (para el token y los presentes)
        data-total      → Total de alumnos inscriptos
        data-token-url  → Endpoint que genera el token rotativo
        data-presentes-url → Endpoint de polling de presentes
        data-finalizar-url → Endpoint para cerrar la clase
        (sin data-scan-base → usa el default: ../alumno/escanear.php)
      -->
      <div class="qr-canvas-wrap" id="qrCanvas"
           data-clase-id="<?= $clase_id ?>"
           data-total="<?= (int) $clase['total_alumnos'] ?>"
           data-token-url="../api/token.php?clase_id=<?= $clase_id ?>"
           data-presentes-url="../api/presentes.php?clase_id=<?= $clase_id ?>"
           data-finalizar-url="../api/finalizar.php">
      </div>

      <div class="qr-countdown" id="countdown">
        <span class="ring">
          <svg width="26" height="26" viewBox="0 0 26 26">
            <circle class="track" cx="13" cy="13" r="11"></circle>
            <circle class="bar" id="ringBar" cx="13" cy="13" r="11"></circle>
          </svg>
        </span>
        <span>Se renueva en <strong id="countdownLabel">00:30</strong></span>
      </div>

      <div>
        <button class="btn btn-danger btn-lg" id="finishBtn">
          <i class="fa-solid fa-stop"></i> Finalizar asistencia
        </button>
      </div>
    </section>

    <!-- Panel lateral de presentes -->
    <aside class="qr-side">
      <div class="presence-card">
        <div class="big-count">
          <span id="presentNow" data-start="<?= (int) $clase['total_alumnos'] ?>">0</span>
          <span class="total">/ <?= (int) $clase['total_alumnos'] ?></span>
        </div>
        <div class="label"><span id="presenceLabel">entradas registradas</span></div>
        <div class="progress"><span id="presentBar" style="width:0%"></span></div>
        <div class="live-list" id="liveList"></div>
      </div>
    </aside>

  </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script src="../assets/js/utils.js"></script>
<script src="../assets/js/qr-display.js"></script>
</body>
</html>
