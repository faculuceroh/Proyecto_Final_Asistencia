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

$page_title = 'Ingresar · Asistencia QR';
$nav_active = 'inicio';
require __DIR__ . '/includes/public_header.php';
?>

  <!-- ===== Hero (izquierda) + Login (derecha) ===== -->
  <main id="inicio" class="auth-wrap auth-wrap-rev">
    <!-- Panel hero con imagen de fondo y texto -->
    <aside class="auth-hero">
      <div class="auth-hero-inner">
        <span class="eyebrow"><i class="fa-solid fa-qrcode"></i> Asistencia inteligente</span>
        <h2>Control de asistencia rápido, simple y sin papeles</h2>
        <p>
          Registrá entradas y salidas con un solo escaneo. Alumnos, profesores y
          secretaría acceden a la información que necesitan, en tiempo real y desde
          cualquier dispositivo.
        </p>
        <ul class="auth-hero-features">
          <li><i class="fa-solid fa-bolt"></i> Registro instantáneo por código QR</li>
          <li><i class="fa-solid fa-chart-line"></i> Reportes y estadísticas al instante</li>
          <li><i class="fa-solid fa-shield-halved"></i> Datos seguros y siempre disponibles</li>
        </ul>
      </div>
    </aside>

    <div class="auth-side">
    <section class="auth-card">
      <div class="auth-logo">
        <img src="assets/img/logo.png" alt="Logo institucional" />
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
    </div>
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
