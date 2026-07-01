<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" /><meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Tomar asistencia · Asistencia QR</title>
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
  <link rel="stylesheet" href="../assets/css/main.css" />
  <link rel="stylesheet" href="../assets/css/dashboard.css" />
  <style>
    .qr-box { background:#fff; border:1px solid var(--c-border); border-radius:16px; padding:24px; text-align:center; max-width:260px; margin:0 auto; }
    .qr-box canvas, .qr-box img { border-radius:8px; }
    .countdown-bar { height:8px; border-radius:4px; background:var(--c-border); margin-top:8px; overflow:hidden; }
    .countdown-bar-fill { height:100%; border-radius:4px; background:var(--c-success); transition:width 1s linear; }
    .countdown-label { font-size:1.5rem; font-weight:800; }
    .session-badge { display:inline-flex; align-items:center; gap:6px; padding:6px 14px; border-radius:20px; font-size:0.85rem; font-weight:700; }
    .session-entrada { background:#dbeafe; color:#1d4ed8; }
    .session-salida  { background:#fef3c7; color:#b45309; }
  </style>
</head>
<body>
<div class="app-layout">
  <aside class="sidebar">
    <div class="sidebar-brand">
      <img src="../assets/img/logo.png" alt="Logo" />
      <div><div class="name">Asistencia QR</div><div class="sub">Portal Profesor</div></div>
    </div>
    <nav class="sidebar-nav">
      <span class="nav-label">Principal</span>
      <a href="dashboard.php"><i class="fa-solid fa-house"></i> Mis clases</a>
      <a href="historial.php"><i class="fa-solid fa-clock-rotate-left"></i> Historial</a>
      <span class="nav-label">Cuenta</span>
      <a href="perfil.php"><i class="fa-solid fa-user"></i> Mi perfil</a>
      <a href="../logout.php"><i class="fa-solid fa-right-from-bracket"></i> Cerrar sesión</a>
    </nav>
    <div class="sidebar-user">
      <?php if (!empty($_SESSION['foto'])): ?><img class="avatar" src="../assets/uploads/perfiles/<?= htmlspecialchars($_SESSION['foto']) ?>" alt="Foto de perfil" /><?php else: ?><div class="avatar"><?= htmlspecialchars($iniciales) ?></div><?php endif; ?>
      <div class="meta">
        <div class="u-name"><?= htmlspecialchars($_SESSION['nombre']) ?></div>
        <div class="u-role">Profesor</div>
      </div>
      <a href="../logout.php" class="logout"><i class="fa-solid fa-right-from-bracket"></i></a>
    </div>
  </aside>
  <div class="sidebar-backdrop" data-sidebar-backdrop></div>

  <div class="app-main">
    <header class="topbar">
      <button class="hamburger" data-sidebar-toggle aria-label="Menú"><i class="fa-solid fa-bars"></i></button>
      <div class="page-title">
        <?= htmlspecialchars($clase['materia']) ?>
        <small><?= htmlspecialchars($clase['curso']) ?> · <?= substr($clase['hora_inicio'],0,5) ?> (<?= $clase['duracion_min'] ?> min)</small>
      </div>
      <div class="topbar-right">
        <a href="dashboard.php" class="btn btn-ghost btn-sm"><i class="fa-solid fa-arrow-left"></i> Volver</a>
      </div>
    </header>

    <main class="app-content">
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:24px;align-items:start">

        <!-- Panel izquierdo: control del QR -->
        <div>
          <div class="card" style="padding:24px" id="controlPanel">

            <!-- Estado: sin sesión activa -->
            <div id="stateIdle">
              <h3 style="font-size:1rem;margin-bottom:16px"><i class="fa-solid fa-door-open"></i> Habilitar QR de entrada</h3>
              <p class="text-muted" style="font-size:0.88rem;margin-bottom:18px">
                Seleccioná el aula donde estás dictando la clase y habilitá el QR para que los alumnos registren su entrada.
              </p>
              <?php if (empty($aulas)): ?>
                <div style="color:var(--c-warning);font-size:0.9rem">
                  <i class="fa-solid fa-triangle-exclamation"></i>
                  No hay aulas configuradas. Pedile al administrador que las cree.
                </div>
              <?php else: ?>
              <div class="field" style="margin-bottom:16px">
                <label>Aula</label>
                <select class="select" id="aulaSelect">
                  <option value="">— Elegí un aula —</option>
                  <?php foreach ($aulas as $a): ?>
                    <option value="<?= $a['id'] ?>" data-token="<?= htmlspecialchars($a['token']) ?>">
                      <?= htmlspecialchars($a['nombre']) ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>
              <button class="btn btn-primary btn-block" id="btnEntrada" disabled>
                <i class="fa-solid fa-right-to-bracket"></i> Habilitar QR de entrada
              </button>
              <?php endif; ?>
            </div>

            <!-- Estado: entrada activa -->
            <div id="stateEntrada" class="hidden">
              <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px">
                <span class="session-badge session-entrada">
                  <i class="fa-solid fa-circle" style="font-size:0.5rem;animation:pulse 1.5s infinite"></i>
                  QR de ENTRADA activo
                </span>
                <span id="aulaNombreEntrada" class="text-muted" style="font-size:0.85rem"></span>
              </div>
              <p class="text-muted" style="font-size:0.88rem;margin-bottom:20px">
                Los alumnos pueden escanear el QR del aula para registrar su entrada.
                El QR permanece activo hasta que actives el de salida.
              </p>
              <button class="btn btn-warning btn-block" id="btnSalida">
                <i class="fa-solid fa-right-from-bracket"></i> Habilitar QR de salida
              </button>
            </div>

            <!-- Estado: salida activa con countdown -->
            <div id="stateSalida" class="hidden">
              <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px">
                <span class="session-badge session-salida">
                  <i class="fa-solid fa-circle" style="font-size:0.5rem;animation:pulse 1.5s infinite"></i>
                  QR de SALIDA activo
                </span>
                <span id="aulaNombreSalida" class="text-muted" style="font-size:0.85rem"></span>
              </div>
              <p class="text-muted" style="font-size:0.88rem;margin-bottom:16px">
                Los alumnos tienen <strong>15 minutos</strong> para escanear el QR de salida.
                Al vencer el tiempo, la clase se finaliza automáticamente.
              </p>
              <div style="text-align:center;margin-bottom:16px">
                <div class="countdown-label" id="countdownLabel">15:00</div>
                <div class="countdown-bar"><div class="countdown-bar-fill" id="countdownFill" style="width:100%"></div></div>
              </div>
              <button class="btn btn-danger btn-block" id="btnFinalizar">
                <i class="fa-solid fa-circle-stop"></i> Finalizar clase
              </button>
            </div>

            <!-- Estado: clase finalizada -->
            <div id="stateFinalizada" class="hidden" style="text-align:center;padding:16px 0">
              <i class="fa-solid fa-circle-check" style="font-size:2.5rem;color:var(--c-success);margin-bottom:12px"></i>
              <h3 style="margin-bottom:8px">¡Clase finalizada!</h3>
              <p class="text-muted" style="font-size:0.9rem">La asistencia fue registrada correctamente.</p>
              <a href="dashboard.php" class="btn btn-primary mt-2">Volver al panel</a>
            </div>

          </div>
        </div>

        <!-- Panel derecho: presentes en tiempo real + QR image -->
        <div style="display:flex;flex-direction:column;gap:16px">
          <!-- Contador -->
          <div class="card" style="padding:24px;text-align:center">
            <div style="font-size:3rem;font-weight:800;color:var(--c-primary)" id="countPresentes">0</div>
            <div style="font-size:0.9rem;color:var(--c-text-soft)">de <strong id="countTotal"><?= $total_inscriptos ?></strong> alumnos presentes</div>
            <div class="progress" style="margin-top:12px">
              <span id="presentBar" style="width:0%;background:var(--c-success)"></span>
            </div>
          </div>

          <!-- QR del aula (visible cuando hay sesión activa) -->
          <div class="card hidden" id="qrCard" style="padding:24px">
            <p style="font-size:0.85rem;color:var(--c-text-soft);text-align:center;margin-bottom:12px">
              QR del aula — los alumnos escanean este código
            </p>
            <div class="qr-box" id="qrContainer"></div>
          </div>
        </div>

      </div>
    </main>
  </div>
</div>

<style>
@keyframes pulse { 0%,100%{opacity:1} 50%{opacity:.4} }
</style>

<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script src="../assets/js/utils.js"></script>
<script>
(function() {
  const CLASE_ID   = <?= $clase_id ?>;
  const TOTAL      = <?= $total_inscriptos ?>;
  const BASE_QR    = <?= json_encode($base_qr) ?>;

  // Vistas
  const stateIdle      = App.qs('#stateIdle');
  const stateEntrada   = App.qs('#stateEntrada');
  const stateSalida    = App.qs('#stateSalida');
  const stateFinalizada= App.qs('#stateFinalizada');
  const qrCard         = App.qs('#qrCard');
  const qrContainer    = App.qs('#qrContainer');

  let currentQR    = null;
  let countdownInt = null;
  let pollInt      = null;
  let expiraTs     = null;
  const SALIDA_SECS = 15 * 60;

  // ── Selector de aula ────────────────────────────────────────
  const aulaSelect  = App.qs('#aulaSelect');
  const btnEntrada  = App.qs('#btnEntrada');
  const btnSalida   = App.qs('#btnSalida');

  if (aulaSelect) {
    aulaSelect.addEventListener('change', () => {
      btnEntrada.disabled = !aulaSelect.value;
    });
  }

  // ── Habilitar entrada ────────────────────────────────────────
  if (btnEntrada) {
    btnEntrada.addEventListener('click', () => {
      const aulaId = aulaSelect.value;
      if (!aulaId) return;
      const token = aulaSelect.options[aulaSelect.selectedIndex].dataset.token;
      const nombre = aulaSelect.options[aulaSelect.selectedIndex].text;
      btnEntrada.disabled = true;
      App.api('../api/habilitar_qr.php', {
        method: 'POST',
        body: JSON.stringify({ clase_id: CLASE_ID, aula_id: parseInt(aulaId), tipo: 'entrada' }),
        loader: true,
      }).then(res => {
        showEntrada(nombre, token);
      }).catch(err => {
        App.toast(err.message, 'error');
        btnEntrada.disabled = false;
      });
    });
  }

  // ── Habilitar salida ─────────────────────────────────────────
  if (btnSalida) {
    btnSalida.addEventListener('click', () => {
      // Recuperar aula_id actual del select (guardado al activar entrada)
      const aulaId = parseInt(stateEntrada.dataset.aulaId || 0);
      if (!aulaId) return;
      btnSalida.disabled = true;
      App.api('../api/habilitar_qr.php', {
        method: 'POST',
        body: JSON.stringify({ clase_id: CLASE_ID, aula_id: aulaId, tipo: 'salida' }),
        loader: true,
      }).then(res => {
        const exp = new Date(res.expira_en_iso);
        showSalida(stateEntrada.dataset.aulaNombre, exp);
      }).catch(err => {
        App.toast(err.message, 'error');
        btnSalida.disabled = false;
      });
    });
  }

  // ── Finalizar clase manualmente ───────────────────────────────
  const btnFinalizar = App.qs('#btnFinalizar');
  if (btnFinalizar) {
    btnFinalizar.addEventListener('click', () => {
      if (!confirm('¿Finalizar la clase ahora? Se deshabilitará el QR de salida y los alumnos que no hayan escaneado quedarán como ausentes.')) return;
      btnFinalizar.disabled = true;
      App.api('../api/cerrar_qr.php', {
        method: 'POST',
        body: JSON.stringify({ clase_id: CLASE_ID }),
        loader: true,
      }).then(() => {
        showFinalizada();
      }).catch(err => {
        App.toast(err.message, 'error');
        btnFinalizar.disabled = false;
      });
    });
  }

  // ── Mostrar vistas ───────────────────────────────────────────
  function setView(view) {
    [stateIdle, stateEntrada, stateSalida, stateFinalizada].forEach(v => v && v.classList.add('hidden'));
    view && view.classList.remove('hidden');
  }

  function showEntrada(aulaNombre, aulaToken, aulaId) {
    stateEntrada.dataset.aulaId     = aulaId != null ? aulaId : (aulaSelect ? aulaSelect.value : '');
    stateEntrada.dataset.aulaNombre = aulaNombre;
    App.qs('#aulaNombreEntrada').textContent = aulaNombre;
    setView(stateEntrada);
    renderQR(aulaToken);
    startPolling();
  }

  function showSalida(aulaNombre, exp) {
    expiraTs = exp.getTime();
    App.qs('#aulaNombreSalida').textContent = aulaNombre;
    setView(stateSalida);
    startCountdown();
  }

  function showFinalizada() {
    clearInterval(countdownInt);
    clearInterval(pollInt);
    qrCard.classList.add('hidden');
    setView(stateFinalizada);
  }

  // ── QR image ──────────────────────────────────────────────
  function renderQR(token) {
    qrContainer.innerHTML = '';
    new QRCode(qrContainer, {
      text: BASE_QR + token,
      width: 200, height: 200,
      colorDark: '#0f172a', colorLight: '#ffffff',
      correctLevel: QRCode.CorrectLevel.M,
    });
    qrCard.classList.remove('hidden');
  }

  // ── Countdown 15 min ─────────────────────────────────────────
  function startCountdown() {
    const fill = App.qs('#countdownFill');
    const label = App.qs('#countdownLabel');

    function tick() {
      const remaining = Math.max(0, Math.round((expiraTs - Date.now()) / 1000));
      const pct = (remaining / SALIDA_SECS) * 100;
      const min = String(Math.floor(remaining / 60)).padStart(2, '0');
      const sec = String(remaining % 60).padStart(2, '0');
      label.textContent = min + ':' + sec;
      fill.style.width = pct + '%';
      fill.style.background = remaining > 120 ? 'var(--c-success)' : remaining > 60 ? 'var(--c-warning)' : 'var(--c-danger)';
      if (remaining === 0) {
        clearInterval(countdownInt);
        // El servidor cierra la sesión de forma lazy; esperamos el siguiente poll
      }
    }
    tick();
    countdownInt = setInterval(tick, 1000);
  }

  // ── Polling de estado ─────────────────────────────────────────
  function startPolling() {
    if (pollInt) clearInterval(pollInt);
    poll();
    pollInt = setInterval(poll, 5000);
  }

  async function poll() {
    try {
      const data = await App.api('../api/estado_qr.php?clase_id=' + CLASE_ID);

      // Actualizar contador de presentes
      const presentes = data.presentes || 0;
      App.qs('#countPresentes').textContent = presentes;
      const pct = TOTAL > 0 ? Math.round(presentes / TOTAL * 100) : 0;
      App.qs('#presentBar').style.width = pct + '%';

      if (data.clase_estado === 'finalizada' || data.finalizada) {
        showFinalizada();
        return;
      }

      // Sync visual state with server (por ejemplo, al volver a esta página
      // con una sesión de QR ya habilitada desde antes)
      if (data.activo && data.tipo === 'salida' && stateSalida.classList.contains('hidden')) {
        const exp = new Date(data.expira_en_iso);
        showSalida(data.aula_nombre, exp);
      } else if (data.activo && data.tipo === 'entrada' && stateEntrada.classList.contains('hidden')) {
        showEntrada(data.aula_nombre, data.aula_token, data.aula_id);
      }
    } catch (_) {}
  }

  // Arrancar polling si ya existe una sesión activa al cargar
  poll();
  pollInt = setInterval(poll, 5000);

})();
</script>
</body>
</html>
