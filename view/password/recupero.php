<?php
// olvide_password.php - Vista para solicitar la recuperación
session_start();

$error_msg = "";
$success_msg = "";

// Atrapamos si el controlador nos devuelve con alguna novedad por la URL
if (isset($_GET['status'])) {
    if ($_GET['status'] == 'empty') {
        $error_msg = "Por favor, ingresá un número de legajo.";
    } elseif ($_GET['status'] == 'error_bd') {
        $error_msg = "Error técnico: No se pudo conectar con la base de datos.";
    } elseif ($_GET['status'] == 'error') {
        $error_msg = "Ocurrió un problema al procesar la solicitud.";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Recuperar Contraseña · Asistencia QR</title>
  
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
        <img src="../../assets/img/logo.png" alt="Logo institucional" />
        <div class="brand">
          <h1>Asistencia QR</h1>
          <p>Portal de Recuperación</p>
        </div>
      </div>

      <form id="recoverForm" autocomplete="off" action="../../controller/recuperarController.php" method="POST">
        
        <div class="field">
          <label for="legajo">Ingresá tu Legajo</label>
          <div class="input-group">
            <i class="input-icon fa-solid fa-id-card"></i>
            <input class="input" type="text" id="legajo" name="legajo" placeholder="Ej: 90001" inputmode="numeric" required />
          </div>
          <p class="field-hint">
            <i class="fa-solid fa-circle-info"></i> El sistema buscará el correo electrónico institucional vinculado a este legajo.
          </p>
        </div>

        <div class="auth-actions">
          <button type="submit" class="btn btn-primary btn-block btn-lg">
            <i class="fa-solid fa-paper-plane"></i> Enviar enlace
          </button>
        </div>

        <a href="../../index.php" class="auth-forgot">
          <i class="fa-solid fa-arrow-left"></i> Volver al inicio de sesión
        </a>
      </form>

      <p class="auth-foot">© 2026 Instituto · Sistema de Asistencia por QR</p>
    </section>
  </main>

  <script src="../../assets/js/utils.js"></script>

  <script>
    // 1. Si PHP nos tiró un error por URL, JS lo muestra flotante al arrancar la página
    const phpError = "<?php echo $error_msg; ?>";
    if (phpError !== "") {
      App.toast(phpError, 'error');
    }

    // 2. Control de seguridad con JS antes de que el formulario viaje
    document.getElementById('recoverForm').addEventListener('submit', function (e) {
      const legajo = document.getElementById('legajo').value.trim();

      if (legajo === '') {
        e.preventDefault();
        App.toast('Por favor, completá el campo de legajo.', 'error');
        return;
      }

      if (isNaN(legajo)) {
        e.preventDefault();
        App.toast('El legajo debe ser un número válido.', 'error');
        return;
      }
      
      // Si pasa los filtros, prende el cargador giratorio y deja viajar el POST al controlador
      App.showLoader();
    });
  </script>
</body>
</html>