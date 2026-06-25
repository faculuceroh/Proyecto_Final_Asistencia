/* ============================================================
   qr-scanner.js — Escáner de QR con cámara (pantalla del alumno)
   Pide acceso a la cámara, lee el QR del profesor y registra la
   asistencia (entrada/salida). Sin PIN.
   Requiere: jsQR (CDN) y utils.js
   ============================================================ */
(function () {
  'use strict';

  const root = document.getElementById('scanRoot');
  if (!root) return;

  // Config inyectada (data-attrs)
  const registrarUrl = root.dataset.registrarUrl || ''; // api/registrar.php (vacío = demo)

  // Vistas
  const promptView = document.getElementById('camPrompt');   // pedido de permiso
  const cameraView = document.getElementById('camView');     // cámara en vivo
  const resultView = document.getElementById('scanResult');  // éxito / error
  const video      = document.getElementById('camVideo');
  const canvas     = document.getElementById('camCanvas');
  const startBtn   = document.getElementById('startCamBtn');
  const cancelBtn  = document.getElementById('cancelCamBtn');
  const demoBtn    = document.getElementById('demoScanBtn');

  const ctx = canvas ? canvas.getContext('2d', { willReadFrequently: true }) : null;
  let stream = null;
  let scanning = false;

  /* ---------- Si el QR se abrió con la cámara nativa (URL con datos) ---------- */
  const params = new URLSearchParams(location.search);
  if (params.get('clase') && params.get('t')) {
    registrar({
      clase: params.get('clase'),
      tipo: params.get('tipo') || 'entrada',
      token: params.get('t'),
    });
    return;
  }

  /* ---------- Mostrar vistas ---------- */
  function show(view) {
    [promptView, cameraView, resultView].forEach((v) => v && v.classList.add('hidden'));
    if (view) view.classList.remove('hidden');
  }

  /* ---------- Iniciar cámara ---------- */
  async function startCamera() {
    if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
      return showError('Tu navegador no permite usar la cámara.', true);
    }
    try {
      stream = await navigator.mediaDevices.getUserMedia({
        video: { facingMode: 'environment' },
        audio: false,
      });
      video.srcObject = stream;
      video.setAttribute('playsinline', 'true'); // iOS
      await video.play();
      show(cameraView);
      scanning = true;
      requestAnimationFrame(tick);
    } catch (err) {
      if (err && (err.name === 'NotAllowedError' || err.name === 'SecurityError')) {
        showError('Necesitamos acceso a la cámara para escanear el QR. Habilitá el permiso e intentá de nuevo.', true);
      } else if (err && err.name === 'NotFoundError') {
        showError('No se encontró ninguna cámara en este dispositivo.', true);
      } else {
        showError('No se pudo abrir la cámara.', true);
      }
    }
  }

  /* ---------- Bucle de lectura ---------- */
  function tick() {
    if (!scanning) return;
    if (video.readyState === video.HAVE_ENOUGH_DATA && window.jsQR) {
      canvas.width = video.videoWidth;
      canvas.height = video.videoHeight;
      ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
      const img = ctx.getImageData(0, 0, canvas.width, canvas.height);
      const code = window.jsQR(img.data, img.width, img.height, { inversionAttempts: 'dontInvert' });
      if (code && code.data) {
        return onDecoded(code.data);
      }
    }
    requestAnimationFrame(tick);
  }

  /* ---------- QR detectado ---------- */
  function onDecoded(text) {
    scanning = false;
    stopCamera();
    const data = parsePayload(text);
    if (!data.clase || !data.token) {
      return showError('El código escaneado no es un QR de asistencia válido.', true);
    }
    registrar(data);
  }

  /** Extrae clase/tipo/token de la URL (o texto) del QR. */
  function parsePayload(text) {
    try {
      const u = new URL(text);
      return {
        clase: u.searchParams.get('clase'),
        tipo: u.searchParams.get('tipo') || 'entrada',
        token: u.searchParams.get('t'),
      };
    } catch (_) {
      // Fallback: "clave=valor" separado por & sin URL completa
      const p = new URLSearchParams(text.replace(/^[^?]*\??/, ''));
      return { clase: p.get('clase'), tipo: p.get('tipo') || 'entrada', token: p.get('t') };
    }
  }

  /* ---------- Detener cámara ---------- */
  function stopCamera() {
    scanning = false;
    if (stream) {
      stream.getTracks().forEach((t) => t.stop());
      stream = null;
    }
  }

  /* ---------- Registrar asistencia ---------- */
  async function registrar(data) {
    const esSalida = (data.tipo || 'entrada').toLowerCase() === 'salida';
    show(resultView);
    resultView.className = 'scan-result';
    resultView.innerHTML = '<div class="spinner spinner-lg" style="margin:30px auto"></div><p>Registrando asistencia…</p>';

    // Demo (sin backend): registra siempre OK.
    if (!registrarUrl) {
      setTimeout(() => showSuccess(esSalida), 700);
      return;
    }
    try {
      const res = await App.api(registrarUrl, {
        method: 'POST',
        body: JSON.stringify({ clase_id: data.clase, token: data.token, tipo: data.tipo }),
      });
      showSuccess(esSalida, res.hora);
    } catch (err) {
      showError(err.message, false);
    }
  }

  /* ---------- Pantallas de resultado ---------- */
  function showSuccess(esSalida, hora) {
    show(resultView);
    resultView.className = 'scan-result success';
    resultView.innerHTML = `
      <div class="result-ico"><i class="fa-solid fa-circle-check"></i></div>
      <h2>${esSalida ? '¡Salida registrada!' : '¡Entrada registrada!'}</h2>
      <div class="stamp"><i class="fa-regular fa-clock"></i> ${esSalida ? 'Salida' : 'Entrada'} ${hora || App.formatTime()}</div>
      <p class="mt-3 text-muted">Ya podés cerrar esta ventana.</p>`;
  }

  function showError(message, allowRetry) {
    stopCamera();
    show(resultView);
    resultView.className = 'scan-result error';
    resultView.innerHTML = `
      <div class="result-ico"><i class="fa-solid fa-circle-xmark"></i></div>
      <h2>No se pudo registrar</h2>
      <p>${message}</p>
      ${allowRetry ? '<button class="btn btn-primary mt-3" id="retryBtn"><i class="fa-solid fa-rotate-left"></i> Reintentar</button>' : ''}`;
    const retry = document.getElementById('retryBtn');
    if (retry) retry.addEventListener('click', () => { show(promptView); });
  }

  /* ---------- Eventos ---------- */
  if (startBtn) startBtn.addEventListener('click', startCamera);
  if (cancelBtn) cancelBtn.addEventListener('click', () => { stopCamera(); show(promptView); });
  if (demoBtn) demoBtn.addEventListener('click', () => registrar({ clase: '1', tipo: 'entrada', token: 'demo' }));

  // Libera la cámara si se cierra/oculta la pestaña
  window.addEventListener('pagehide', stopCamera);
  document.addEventListener('visibilitychange', () => { if (document.hidden) stopCamera(); });

  show(promptView);
})();
