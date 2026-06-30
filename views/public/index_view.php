  <main id="inicio" class="auth-wrap auth-wrap-rev">

    <!-- Panel hero -->
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

    <!-- Panel login -->
    <div class="auth-side">
      <section class="auth-card">
        <header class="auth-logo">
          <img src="assets/img/logo.png" alt="Logo de Asistencia QR" width="60" height="60" />
          <div class="brand">
            <h1 class="brand-title">Asistencia QR</h1>
            <p>Instituto · Portal de acceso</p>
          </div>
        </header>

        <form id="loginForm" autocomplete="on" novalidate>
          <div class="field">
            <label for="legajo">Legajo</label>
            <div class="input-group">
              <i class="input-icon fa-solid fa-id-card"></i>
              <input class="input" type="text" id="legajo" name="legajo"
                     placeholder="Ej: 20451" inputmode="numeric" required />
            </div>
          </div>

          <div class="field">
            <label for="password">Contraseña</label>
            <div class="input-group">
              <i class="input-icon fa-solid fa-lock"></i>
              <input class="input" type="password" id="password" name="password"
                     placeholder="••••••••" required />
              <button type="button" id="togglePass" class="input-toggle"
                      aria-label="Mostrar contraseña">
                <i class="fa-solid fa-eye" id="toggleIcon"></i>
              </button>
            </div>
          </div>

          <div class="auth-actions">
            <button type="submit" class="btn btn-primary btn-block btn-lg">
              <i class="fa-solid fa-right-to-bracket"></i> Ingresar
            </button>
          </div>

          <a href="password/recupero.php" class="auth-forgot">¿Olvidaste tu contraseña?</a>
        </form>

        <p class="auth-foot">© 2026 Instituto · Sistema de Asistencia por QR</p>
      </section>
    </div>
  </main>

  <script src="assets/js/utils.js"></script>
  <script>
    // Mensajes de estado (recuperación de contraseña, etc.)
    (function () {
      const p = new URLSearchParams(location.search);
      const s = p.get('status');
      if (s === 'enviado')        App.toast('Si el legajo tiene un email registrado, recibirás el enlace en breve.', 'success');
      if (s === 'clave_cambiada') App.toast('Contraseña actualizada. Ya podés iniciar sesión.', 'success');
      if (s === 'error')          App.toast('Ocurrió un error. Intentá de nuevo.', 'error');
    })();

    // Toggle mostrar/ocultar contraseña
    document.getElementById('togglePass').addEventListener('click', function () {
      const input = document.getElementById('password');
      const icon  = document.getElementById('toggleIcon');
      const show  = input.type === 'password';
      input.type  = show ? 'text' : 'password';
      icon.classList.toggle('fa-eye',      !show);
      icon.classList.toggle('fa-eye-slash', show);
    });

    const legajoInput = document.getElementById('legajo');

    // Submit via API JSON (mismo mecanismo que el index.html original)
    document.getElementById('loginForm').addEventListener('submit', function (e) {
      e.preventDefault();
      const legajo   = legajoInput.value.trim();
      const password = this.password.value;
      if (!legajo || !password) {
        App.toast('Completá legajo y contraseña.', 'error');
        return;
      }
      App.api('login.php', {
        method: 'POST',
        loader: true,
        body: JSON.stringify({ legajo, password }),
      })
      .then(function (data) {
        App.toast('Bienvenido, ' + data.nombre + '!', 'success');
        const params   = new URLSearchParams(location.search);
        let redirect   = params.get('redirect');
        if (redirect) {
          try {
            const u = new URL(redirect);
            u.searchParams.delete('t');
            redirect = u.toString();
          } catch (_) {}
        }
        setTimeout(() => (window.location.href = redirect || data.dest), 800);
      })
      .catch(function (err) {
        App.toast(err.message, 'error');
      });
    });
  </script>
