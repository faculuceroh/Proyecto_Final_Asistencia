/* ============================================================
   qr-display.js — QR rotativo con countdown (pantalla del profesor)
   Requiere: qrcode.min.js (CDN) y utils.js
   ============================================================ */
(function () {
  'use strict';

  const ROTATE_SECONDS = 30;          // se renueva cada 30s
  const LOW_THRESHOLD = 8;            // umbral para el aviso "se renueva pronto"

  const wrap        = document.getElementById('qrCanvas');
  const countLabel  = document.getElementById('countdownLabel');
  const countdown   = document.getElementById('countdown');
  const ringBar     = document.getElementById('ringBar');
  const presentNow  = document.getElementById('presentNow');
  const presentBar  = document.getElementById('presentBar');
  const liveList    = document.getElementById('liveList');

  if (!wrap) return;

  // Config inyectada desde el HTML (data-attrs): id de clase y endpoint del token
  const claseId   = wrap.dataset.claseId || 'demo';
  const tokenUrl  = wrap.dataset.tokenUrl || '';   // ej: ../api/token.php?clase_id=X
  const scanBase  = wrap.dataset.scanBase || '../alumno/escanear.php';
  const totalAlum = parseInt(wrap.dataset.total || '30', 10);

  let qr = null;
  let remaining = ROTATE_SECONDS;
  let presentes = parseInt(presentNow ? presentNow.dataset.start || '0' : '0', 10);

  // Modo del QR: 'entrada' o 'salida' (toggle del profesor)
  let modo = (document.querySelector('input[name="qrMode"]:checked') || {}).value || 'entrada';
  const panel = document.querySelector('.qr-panel');
  const presenceLabel = document.getElementById('presenceLabel');

  function applyModeUI() {
    if (panel) panel.classList.toggle('mode-salida', modo === 'salida');
    if (presenceLabel) {
      presenceLabel.textContent = modo === 'salida' ? 'salidas registradas' : 'entradas registradas';
    }
  }

  document.querySelectorAll('input[name="qrMode"]').forEach((r) => {
    r.addEventListener('change', () => {
      modo = r.value;
      applyModeUI();
      renderQR();              // nuevo QR al cambiar de modo
      App.toast('QR de ' + modo + ' activo.', 'info');
    });
  });

  // Circunferencia para el anillo SVG (r=11)
  const CIRC = 2 * Math.PI * 11;
  if (ringBar) {
    ringBar.style.strokeDasharray = CIRC;
    ringBar.style.strokeDashoffset = 0;
  }

  /* ---------- Obtener token y construir URL del QR ---------- */
  async function fetchToken() {
    if (!tokenUrl) {
      // Modo demo (sin backend): token pseudo-aleatorio
      return 'demo-' + Math.random().toString(36).slice(2, 10);
    }
    try {
      const data = await App.api(tokenUrl);
      return data.token;
    } catch (err) {
      App.toast('No se pudo renovar el QR. Reintentando…', 'error');
      return 'retry-' + Date.now();
    }
  }

  function buildScanUrl(token) {
    const sep = scanBase.includes('?') ? '&' : '?';
    return `${location.origin}${resolvePath(scanBase)}${sep}clase=${encodeURIComponent(claseId)}&tipo=${encodeURIComponent(modo)}&t=${encodeURIComponent(token)}`;
  }
  function resolvePath(p) {
    // Resuelve rutas relativas a absolutas para que el QR funcione en cualquier dispositivo
    const a = document.createElement('a');
    a.href = p;
    return a.pathname + a.search;
  }

  /* ---------- Renderizar QR con transición ---------- */
  async function renderQR() {
    const token = await fetchToken();
    const url = buildScanUrl(token);

    wrap.classList.add('is-refreshing');          // fade out
    setTimeout(() => {
      wrap.innerHTML = '';
      qr = new QRCode(wrap, {
        text: url,
        width: 280,
        height: 280,
        colorDark: '#1a2744',
        colorLight: '#ffffff',
        correctLevel: QRCode.CorrectLevel.M,
      });
      wrap.classList.remove('is-refreshing');     // fade in
    }, 380);
  }

  /* ---------- Countdown ---------- */
  function updateCountdown() {
    if (countLabel) {
      const mm = String(Math.floor(remaining / 60)).padStart(2, '0');
      const ss = String(remaining % 60).padStart(2, '0');
      countLabel.textContent = `${mm}:${ss}`;
    }
    if (ringBar) {
      const offset = CIRC * (1 - remaining / ROTATE_SECONDS);
      ringBar.style.strokeDashoffset = offset;
    }
    if (countdown) countdown.classList.toggle('is-low', remaining <= LOW_THRESHOLD);
  }

  function tick() {
    remaining--;
    if (remaining <= 0) {
      remaining = ROTATE_SECONDS;
      renderQR();
    }
    updateCountdown();
  }

  /* ---------- Polling de presentes (demo: simulado) ---------- */
  function updatePresence(count, nombre, iniciales) {
    presentes = count;
    if (presentNow) presentNow.textContent = presentes;
    if (presentBar) presentBar.style.width = Math.min(100, (presentes / totalAlum) * 100) + '%';
    if (liveList && nombre) {
      const item = document.createElement('div');
      item.className = 'live-item';
      item.innerHTML = `<div class="mini-avatar">${iniciales}</div>
        <span>${nombre}</span><span class="t">${App.formatTime()}</span>`;
      liveList.prepend(item);
    }
  }

  async function pollPresence() {
    // En producción: GET al endpoint que devuelve { presentes, nuevos: [...] }
    // Demo: simula que de a poco van escaneando.
    const demoNames = [
      ['Lucía Gómez', 'LG'], ['Tomás Pérez', 'TP'], ['Sofía Díaz', 'SD'],
      ['Iván Roldán', 'IR'], ['Mara Sosa', 'MS'], ['Nico Vera', 'NV'],
    ];
    let i = 0;
    setInterval(() => {
      if (i < demoNames.length && presentes < totalAlum) {
        updatePresence(presentes + 1, demoNames[i][0], demoNames[i][1]);
        i++;
      }
    }, 4000);
  }

  /* ---------- Finalizar ---------- */
  const finishBtn = document.getElementById('finishBtn');
  if (finishBtn) {
    finishBtn.addEventListener('click', () => {
      if (!confirm('¿Finalizar la toma de asistencia? El QR dejará de funcionar.')) return;
      App.toast('Asistencia finalizada. ' + presentes + ' presentes registrados.', 'success', { title: 'Cerrada' });
      finishBtn.disabled = true;
      // En producción: POST a ../api/finalizar.php y redirigir al historial
      setTimeout(() => (window.location.href = 'historial.php'), 1200);
    });
  }

  /* ---------- Init ---------- */
  applyModeUI();
  renderQR();
  updateCountdown();
  setInterval(tick, 1000);
  pollPresence();
})();
