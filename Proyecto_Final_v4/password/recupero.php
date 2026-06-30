<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" /><meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Recuperar contraseña · Asistencia QR</title>
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
  <link rel="stylesheet" href="../assets/css/main.css" />
  <link rel="stylesheet" href="../assets/css/auth.css" />
</head>
<body>
  <main class="auth-wrap auth-wrap--center">
    <section class="auth-card">
      <div class="auth-logo">
        <img src="../assets/img/logo.png" alt="Logo institucional" />
        <div class="brand">
          <h1>Asistencia QR</h1>
          <p>Recuperar contraseña</p>
        </div>
      </div>

      <form id="recoverForm" autocomplete="off" action="../controller/recuperarController.php" method="POST">
        <div class="field">
          <label for="legajo">Legajo</label>
          <div class="input-group">
            <i class="input-icon fa-solid fa-id-card"></i>
            <input class="input" type="text" id="legajo" name="legajo"
                   placeholder="Ej: 20451" inputmode="numeric" required />
          </div>
          <p class="field-hint">
            <i class="fa-solid fa-circle-info"></i>
            Te enviaremos un enlace al email institucional vinculado a tu legajo.
          </p>
        </div>

        <div class="auth-actions">
          <button type="submit" class="btn btn-primary btn-block btn-lg">
            <i class="fa-solid fa-paper-plane"></i> Enviar enlace
          </button>
        </div>

        <a href="../index.php" class="auth-forgot">
          <i class="fa-solid fa-arrow-left"></i> Volver al inicio de sesión
        </a>
      </form>

      <p class="auth-foot">© 2026 Instituto · Sistema de Asistencia por QR</p>
    </section>
  </main>

  <script src="../assets/js/utils.js"></script>
  <script>
    <?php
    $status = $_GET['status'] ?? '';
    $msgs = [
      'empty'    => ['Por favor, ingresá un número de legajo.', 'error'],
      'error'    => ['Ocurrió un problema al enviar el correo. Intentá de nuevo.', 'error'],
      'error_bd' => ['Error al conectar con la base de datos.', 'error'],
    ];
    if (isset($msgs[$status])):
    ?>
    App.toast(<?= json_encode($msgs[$status][0]) ?>, <?= json_encode($msgs[$status][1]) ?>);
    <?php endif; ?>

    document.getElementById('recoverForm').addEventListener('submit', function (e) {
      const legajo = document.getElementById('legajo').value.trim();
      if (!legajo) {
        e.preventDefault();
        App.toast('Completá el campo de legajo.', 'error');
        return;
      }
      if (isNaN(legajo)) {
        e.preventDefault();
        App.toast('El legajo debe ser un número válido.', 'error');
        return;
      }
      App.showLoader && App.showLoader();
    });
  </script>
</body>
</html>
