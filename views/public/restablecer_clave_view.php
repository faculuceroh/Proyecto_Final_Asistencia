<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" /><meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Restablecer contraseña · Asistencia QR</title>
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="../assets/vendor/fontawesome/css/all.min.css" />
  <link rel="stylesheet" href="../assets/css/main.css" />
  <link rel="stylesheet" href="../assets/css/auth.css" />
</head>
<body>
  <main class="auth-wrap">
    <section class="auth-card">
      <div class="auth-logo">
        <img src="../assets/img/logo.png" alt="Logo" />
        <div class="brand">
          <h1>Asistencia QR</h1>
          <p>Nueva contraseña</p>
        </div>
      </div>

      <?php if ($token_valido): ?>
        <form id="resetForm" autocomplete="off"
              action="../controller/cambiar_password_controlador.php" method="POST">
          <input type="hidden" name="user_id" value="<?= $user_id ?>" />
          <input type="hidden" name="token"   value="<?= htmlspecialchars($token) ?>" />

          <div class="field">
            <label for="password">Nueva contraseña</label>
            <div class="input-group">
              <i class="input-icon fa-solid fa-lock"></i>
              <input class="input" type="password" id="password" name="password"
                     placeholder="Mínimo 6 caracteres" required />
            </div>
          </div>
          <div class="field">
            <label for="confirm_password">Confirmar contraseña</label>
            <div class="input-group">
              <i class="input-icon fa-solid fa-lock"></i>
              <input class="input" type="password" id="confirm_password"
                     name="confirm_password" placeholder="Repetí tu contraseña" required />
            </div>
          </div>

          <div class="auth-actions">
            <button type="submit" class="btn btn-primary btn-block btn-lg">
              <i class="fa-solid fa-key"></i> Actualizar contraseña
            </button>
          </div>
        </form>

      <?php else: ?>
        <div style="text-align:center;padding:20px 0">
          <i class="fa-solid fa-circle-exclamation" style="font-size:3rem;color:var(--c-danger);margin-bottom:16px"></i>
          <p style="font-weight:600;margin-bottom:8px"><?= htmlspecialchars($error_msg) ?></p>
          <a href="recupero.php" class="btn btn-primary mt-3">
            <i class="fa-solid fa-rotate-left"></i> Solicitar nuevo enlace
          </a>
        </div>
      <?php endif; ?>

      <p class="auth-foot">© 2026 Instituto · Sistema de Asistencia por QR</p>
    </section>
  </main>

  <script src="../assets/js/utils.js"></script>
  <?php if ($token_valido): ?>
  <script>
    document.getElementById('resetForm').addEventListener('submit', function (e) {
      const pass    = document.getElementById('password').value;
      const confirm = document.getElementById('confirm_password').value;
      if (pass.length < 6) {
        e.preventDefault();
        App.toast('La contraseña debe tener al menos 6 caracteres.', 'error');
        return;
      }
      if (pass !== confirm) {
        e.preventDefault();
        App.toast('Las contraseñas no coinciden.', 'error');
        return;
      }
    });
  </script>
  <?php endif; ?>
</body>
</html>
