<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Si ya está logueado, redirigir a su dashboard correspondiente
if (isset($_SESSION['rol']) && isset($_SESSION['usuario_id'])) {
    $destinos = [
        'alumno'     => 'alumno/dashboard.php',
        'profesor'   => 'profesor/dashboard.php',
        'secretaria' => 'secretaria/exportar.php',
        'admin'      => 'admin/dashboard.php',
    ];
    if (isset($destinos[$_SESSION['rol']])) {
        header("Location: " . $destinos[$_SESSION['rol']]);
        exit();
    }
}

$error_msg = "";
if (isset($_GET['error'])) {
    if ($_GET['error'] == '1') {
        $error_msg = "Legajo o contraseña incorrectos.";
    } elseif ($_GET['error'] == 'bd') {
        $error_msg = "Problema técnico con la base de datos.";
    } elseif ($_GET['error'] == 'desactivado') {
        $error_msg = "Tu cuenta está desactivada. Contactá a secretaría.";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Ingresar · Asistencia QR</title>

  <!-- Google Fonts: Plus Jakarta Sans -->
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet" />

  <!-- Íconos -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />

  <!-- Estilos -->
  <link rel="stylesheet" href="assets/css/main.css" />
  <link rel="stylesheet" href="assets/css/auth.css" />
</head>
<body>
  <main class="auth-wrap">
    <section class="auth-card">
      <div class="auth-logo">
        <img src="assets/img/logo.png" alt="Logo" />
        <div class="brand">
          <h1>Asistencia QR</h1>
          <p>Instituto · Portal de acceso</p>
        </div>
      </div>

      <form id="loginForm" autocomplete="on" action="controller/login.php" method="POST">
        
        <div class="field">
          <label for="legajo">Legajo</label>
          <div class="input-group">
            <i class="input-icon fa-solid fa-id-card"></i>
            <input class="input" type="text" id="legajo" name="legajo" placeholder="Ej: 20451" inputmode="numeric" required />
          </div>
          <p class="field-hint" id="rolHint">
            <i class="fa-solid fa-circle-info"></i> Tu rol se reconoce automáticamente por tu legajo.
          </p>
        </div>

        <div class="field">
          <label for="password">Contraseña</label>
          <div class="input-group">
            <i class="input-icon fa-solid fa-lock"></i>
            <input class="input" type="password" id="password" name="password" placeholder="••••••••" required />
          </div>
        </div>

        <div class="auth-actions">
          <button type="submit" class="btn btn-primary btn-block btn-lg">
            <i class="fa-solid fa-right-to-bracket"></i> Ingresar
          </button>
        </div>

        <a href="#" class="auth-forgot">¿Olvidaste tu contraseña?</a>
      </form>

      <p class="auth-foot">© 2026 Instituto · Sistema de Asistencia por QR</p>
    </section>
  </main>

  <script src="assets/js/utils.js"></script>
  <script>
    // 1. Mostrar cartel flotante de error si PHP nos redireccionó con una falla
    const phpError = "<?php echo $error_msg; ?>";
    if (phpError !== "") {
      App.toast(phpError, 'error');
    }

    // 2. Detección visual de roles en tiempo real
    const ROLES = {
      '2': { label: 'Alumno' },
      '1': { label: 'Profesor' },
      '3': { label: 'Secretaría' },
      '9': { label: 'Admin' },
    };

    const legajoInput = document.getElementById('legajo');
    const hint = document.getElementById('rolHint');
    const loginForm = document.getElementById('loginForm');

    legajoInput.addEventListener('input', function () {
      const primerDigito = this.value.trim()[0] || '';
      const r = ROLES[primerDigito] || null;
      
      hint.innerHTML = r
        ? '<i class="fa-solid fa-circle-check"></i> Ingresás como <strong>' + r.label + '</strong>'
        : '<i class="fa-solid fa-circle-info"></i> Tu rol se reconoce automáticamente por tu legajo.';
      hint.classList.toggle('is-ok', !!r);
    });

    // 3. Validación rápida de JavaScript para evitar enviar campos vacíos
    loginForm.addEventListener('submit', function (e) {
      const legajo = legajoInput.value.trim();
      const password = document.getElementById('password').value;

      if (legajo === '' || password === '') {
        e.preventDefault(); // Frena el viaje
        App.toast('Por favor, completá todos los campos.', 'error');
        return;
      }

      if (isNaN(legajo)) {
        e.preventDefault(); // Frena el viaje
        App.toast('El legajo debe ser un número válido.', 'error');
        return;
      }
      
      // Si pasa, se ejecuta el envío tradicional POST a login.php y se muestra el cargador visual
      App.showLoader();
    });
  </script>
</body>
</html>