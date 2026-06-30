/* ============================================================
   qr-scanner.js — Escáner de QR con cámara (pantalla del alumno)
   Pide acceso a la cámara, lee el QR del profesor y registra la
   asistencia (entrada/salida). Con geolocalización.
   Requiere: jsQR (CDN) y utils.js
   ============================================================ */
(function () {
  'use strict';

  const root = document.getElementById('scanRoot');
  if (!root) return;

  // Config inyectada (data-attrs)
  const registrarUrl = root.dataset.registrarUrl || ''; // api/registrar.php (vacío = demo)
  const verificarRangoUrl = root.dataset.verificarRangoUrl || '../api/verificar_rango.php';

  // Vistas
  const promptView = document.getElementById('camPrompt');   // pedido de permiso
  const cameraView = document.getElementById('camView');     // cámara en vivo
  const resultView = document.getElementById('scanResult');  // éxito / error
  const geoLoadingView = document.getElementById('geoLoadingView'); // spinner geolocalización
  const video = document.getElementById('camVideo');
  const canvas = document.getElementById('camCanvas');
  const startBtn = document.getElementById('startCamBtn');
  const cancelBtn = document.getElementById('cancelCamBtn');
  const demoBtn = document.getElementById('demoScanBtn');

  const ctx = canvas ? canvas.getContext('2d', { willReadFrequently: true }) : null;
  let stream = null;
  let scanning = false;
  let userCoords = null; // Coordenadas del alumno ya verificadas

  // Parámetros de URL si se abrió con escáner nativo
  const params = new URLSearchParams(location.search);
  const qrClase = params.get('clase');
  const qrToken = params.get('t');
  const qrTipo = params.get('tipo') || 'entrada';

  /* ---------- Mostrar vistas ---------- */
  function show(view) {
    [promptView, cameraView, resultView, geoLoadingView].forEach((v) => v && v.classList.add('hidden'));
    if (view) view.classList.remove('hidden');
  }

  /* ---------- Iniciar verificación de GPS ---------- */
  function iniciarVerificacionUbicacion() {
    if (!navigator.geolocation) {
      showGeoError("Tu navegador o dispositivo no admite geolocalización.", false);
      return;
    }

    show(geoLoadingView);

    navigator.geolocation.getCurrentPosition(
      async function (position) {
        const latActual = position.coords.latitude;
        const lonActual = position.coords.longitude;
        const accuracy = position.coords.accuracy;

        console.log(`GPS capturado: Lat ${latActual}, Lon ${lonActual}, Precisión ${accuracy}m`);

        // Validar precisión aceptable
        const coordenadasValidas = Number.isFinite(latActual) && Number.isFinite(lonActual) && !(latActual === 0 && lonActual === 0) && Number.isFinite(accuracy) && accuracy <= 150;

        if (!coordenadasValidas) {
          showGeoError("La ubicación detectada no es válida o tiene poca precisión (más de 150 metros de margen de error). Intenta nuevamente en un espacio abierto con mejor señal GPS.", true);
          return;
        }

        try {
          // Enviar coordenadas al backend para verificar rango
          const response = await fetch(verificarRangoUrl, {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json'
            },
            body: JSON.stringify({
              lat: latActual,
              lon: lonActual
            })
          });

          if (!response.ok) {
            throw new Error('Error al validar la ubicación en el servidor.');
          }

          const data = await response.json();
          console.log("Respuesta del servidor geo:", data);

          if (data && data.status === 'success' && data.habilitar_camara === true) {
            // Ubicación correcta: Guardamos las coordenadas validadas
            userCoords = { lat: latActual, lon: lonActual };

            // Si el QR ya vino en la URL (escaneo directo con cámara nativa)
            if (qrClase && qrToken) {
              registrar({
                clase: qrClase,
                tipo: qrTipo,
                token: qrToken,
              });
            } else {
              // Sino, mostramos el prompt para que elija abrir la cámara en la app
              show(promptView);
            }
          } else {
            const extraInfo = `<br><span style="font-size:0.85rem; display:block; margin-top:10px; opacity:0.85;">Tus coordenadas detectadas:<br><strong>Lat: ${latActual.toFixed(6)}, Lon: ${lonActual.toFixed(6)}</strong></span>`;
            showGeoError(`Acceso denegado: ${data?.message || 'Estás fuera del rango permitido.'}${extraInfo}`, true);
          }
        } catch (error) {
          console.error("Error al conectar con la API de ubicación:", error);
          showGeoError("Hubo un error de conexión al verificar el rango de tu ubicación con la universidad.", true);
        }
      },
      function (error) {
        let msg = "Para registrar asistencia es obligatorio permitir el acceso al GPS de tu dispositivo.";
        if (error.code === error.PERMISSION_DENIED) {
          msg = "Permiso de ubicación denegado. Por favor, habilita los permisos de GPS en tu navegador para continuar.";
        } else if (error.code === error.POSITION_UNAVAILABLE) {
          msg = "La señal GPS no está disponible en este momento. Verifica tu conexión.";
        } else if (error.code === error.TIMEOUT) {
          msg = "Se agotó el tiempo de espera para obtener tu ubicación GPS.";
        }
        showGeoError(`Error de GPS: ${msg}`, true);
      },
      {
        enableHighAccuracy: true, // Forzar GPS de alta precisión
        timeout: 10000,
        maximumAge: 0
      }
    );
  }

  /* ---------- Mostrar error de Geolocalización ---------- */
  function showGeoError(message, allowRetry) {
    stopCamera();
    show(resultView);
    resultView.className = 'scan-result error';
    resultView.innerHTML = `
      <div class="result-ico"><i class="fa-solid fa-location-dot"></i></div>
      <h2>Ubicación requerida</h2>
      <p>${message}</p>
      ${allowRetry ? '<button class="btn btn-primary mt-3" id="retryGeoBtn"><i class="fa-solid fa-rotate-left"></i> Reintentar verificación</button>' : ''}
      <a href="dashboard.php" class="btn btn-ghost btn-block mt-2"><i class="fa-solid fa-arrow-left"></i> Volver al inicio</a>`;

    const retryGeoBtn = document.getElementById('retryGeoBtn');
    if (retryGeoBtn) {
      retryGeoBtn.addEventListener('click', iniciarVerificacionUbicacion);
    }
  }

  /* ---------- Iniciar cámara ---------- */
  async function startCamera() {
    // Si por alguna razón el usuario se saltó la validación o no hay coordenadas
    if (!userCoords) {
      iniciarVerificacionUbicacion();
      return;
    }

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
    // Validar coordenadas de geolocalización antes de registrar
    if (!userCoords) {
      showGeoError("Se requiere validación de geolocalización para poder registrar la asistencia.", true);
      return;
    }

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
      <p class="mt-3 text-muted">Ya podés cerrar esta ventana.</p>
      <a href="dashboard.php" class="btn btn-success btn-block mt-3" style="font-size:0.85rem">
        <i class="fa-solid fa-arrow-left"></i> Volver al inicio
      </a>`;
  }

  function showError(message, allowRetry) {
    stopCamera();
    show(resultView);
    resultView.className = 'scan-result error';
    resultView.innerHTML = `
      <div class="result-ico"><i class="fa-solid fa-circle-xmark"></i></div>
      <h2>No se pudo registrar</h2>
      <p>${message}</p>
      ${allowRetry ? '<button class="btn btn-primary mt-3" id="retryBtn"><i class="fa-solid fa-rotate-left"></i> Reintentar</button>' : ''}
      <a href="dashboard.php" class="btn btn-ghost btn-block mt-2" style="font-size:0.85rem">
        <i class="fa-solid fa-arrow-left"></i> Volver al inicio
      </a>`;

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

  // Iniciar la verificación de ubicación en el momento en que se carga la página
  iniciarVerificacionUbicacion();
})();