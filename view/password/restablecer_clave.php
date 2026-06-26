<?php
// restablecer_clave.php - Validación de Token y Formulario
session_start();

require_once __DIR__ . '/../../includes/db.php';

$token_valido = false;
$user_id = null;
$error_msg = "";

// 🛡️ 1. VERIFICACIÓN: Validamos el token apenas se carga la página
if (isset($_GET['token']) && !empty(trim($_GET['token']))) {
    $token = trim($_GET['token']);

    try {
        // Buscamos si el token existe y si la hora actual es MENOR a la hora de expiración
        $stmt = getPDO()->prepare('SELECT id, token_expira FROM usuarios WHERE token_recuperacion = ? LIMIT 1');
        $stmt->execute([$token]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            $ahora = date("Y-m-d H:i:s");
            
            // Si la fecha de expiración es mayor que "ahora", el token sigue vigente
            if ($user['token_expira'] >= $ahora) {
                $token_valido = true;
                $user_id = $user['id']; // Nos guardamos el ID para saber a quién cambiarle la clave
            } else {
                $error_msg = "El enlace ha expirado. Por seguridad, los tokens solo duran 1 hora.";
            }
        } else {
            $error_msg = "El enlace de recuperación es inválido o ya fue utilizado.";
        }
    } catch (PDOException $e) {
        $error_msg = "Error técnico en el servidor.";
    }
} else {
    $error_msg = "Acceso no autorizado. Falta el token de seguridad.";
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Restablecer Contraseña · Asistencia QR</title>
  
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
  
  <link rel="stylesheet" href="../../assets/css/main.css" />
  <link rel="stylesheet" href="../../assets/css/auth.css" />
</head>
<body>
  <main class="auth-wrap">
    <section class="auth-card">
      <div class="auth-logo">
        <img src="../../assets/img/logo.png" alt="Logo" />
        <div class="brand">
          <h1>Asistencia QR</h1>
          <p>Restablecer Contraseña</p>
        </div>
      </div>

      <?php if ($token_valido): ?>
        <form id="resetForm" autocomplete="off" action="../../controller/cambiar_password_controlador.php" method="POST">
          
          <input type="hidden" name="user_id" value="<?php echo $user_id; ?>" />
          <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>" />

          <div class="field">
            <label for="password">Nueva Contraseña</label>
            <div class="input-group">
              <i class="input-icon fa-solid fa-lock"></i>
              <input class="input" type="password" id="password" name="password" placeholder="Mínimo 6 caracteres" required />
            </div>
          </div>

          <div class="field">
            <label for="confirm_password">Confirmar Contraseña</label>
            <div class="input-group">
              <i class="input-icon fa-solid fa-lock"></i>
              <input class="input" type="password" id="confirm_password" name="confirm_password" placeholder="Repetí tu contraseña" required />
            </div>
          </div>

          <div class="auth-actions">
            <button type="submit" class="btn btn-primary btn-block btn-lg">
              <i class="fa-solid fa-key"></i> Actualizar Contraseña
            </button>
          </div>
        </form>

      <?php else: ?>
        <div class="auth-error-state" style="text-align: center; padding: 20px 0;">
            <i class="fa-solid fa-circle-exclamation" style="font-size: 48px; color: #dc3545; margin-bottom: 15px;"></i>
            <p style="color: #333; font-weight: 600; margin-bottom: 10px;"><?php echo $error_msg; ?></p>
            <p style="color: #666; font-size: 14px; margin-bottom: 25px;">Por favor, volvé a solicitar la recuperación.</p>
            <a href="olvide_password.php" class="btn btn-secondary" style="text-decoration: none; padding: 10px 20px; background: #6c757d; color: white; border-radius: 5px;">
                Solicitar nuevo enlace
            </a>
        </div>
      <?php endif; ?>

      <p class="auth-foot">© 2026 Instituto · Sistema de Asistencia por QR</p>
    </section>
  </main>

  <script src="assets/js/utils.js"></script>
  <script>
    // Validación rápida con JS antes de enviar al servidor
    if (document.getElementById('resetForm')) {
        document.getElementById('resetForm').addEventListener('submit', function (e) {
          const pass = document.getElementById('password').value;
          const confirmPass = document.getElementById('confirm_password').value;

          if (pass.length < 6) {
            e.preventDefault();
            alert('La contraseña debe tener al menos 6 caracteres.');
            return;
          }

          if (pass !== confirmPass) {
            e.preventDefault();
            alert('Las contraseñas no coinciden.');
            return;
          }
        });
    }
  </script>
</body>
</html>