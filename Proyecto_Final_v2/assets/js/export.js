/* ============================================================
   export.js — Exportar a Excel y enviar a secretaría
   Requiere utils.js
   ============================================================ */
(function () {
  'use strict';

  /**
   * Descarga un archivo desde una URL (el backend devuelve el .xlsx).
   * Muestra spinner en el botón y un toast al terminar.
   */
  async function exportExcel(url, btn) {
    const original = btn ? btn.innerHTML : '';
    if (btn) {
      btn.disabled = true;
      btn.innerHTML = '<span class="spinner"></span> Generando…';
    }
    try {
      const res = await fetch(url);
      if (!res.ok) throw new Error('No se pudo generar el archivo (' + res.status + ').');

      const blob = await res.blob();
      // Nombre desde Content-Disposition si viene, si no uno por defecto
      const cd = res.headers.get('content-disposition') || '';
      const match = cd.match(/filename="?([^"]+)"?/);
      const filename = match ? match[1] : 'asistencia.xlsx';

      const link = document.createElement('a');
      link.href = URL.createObjectURL(blob);
      link.download = filename;
      document.body.appendChild(link);
      link.click();
      link.remove();
      URL.revokeObjectURL(link.href);

      App.toast('Archivo descargado: ' + filename, 'success', { title: 'Exportado' });
    } catch (err) {
      App.toast(err.message, 'error');
    } finally {
      if (btn) { btn.disabled = false; btn.innerHTML = original; }
    }
  }

  /** Envía el reporte a secretaría (POST). */
  async function enviarSecretaria(url, claseId, btn) {
    const original = btn ? btn.innerHTML : '';
    if (btn) {
      btn.disabled = true;
      btn.innerHTML = '<span class="spinner"></span> Enviando…';
    }
    try {
      await App.api(url, {
        method: 'POST',
        body: JSON.stringify({ clase_id: claseId }),
      });
      App.toast('Reporte enviado a secretaría.', 'success');
      if (btn) {
        btn.innerHTML = '<i class="fa-solid fa-check"></i> Enviado';
        btn.classList.remove('btn-ghost');
        btn.classList.add('btn-success');
      }
    } catch (err) {
      App.toast(err.message, 'error');
      if (btn) { btn.disabled = false; btn.innerHTML = original; }
    }
  }

  /* ---------- Auto-binding por data-attrs ---------- */
  document.addEventListener('click', (e) => {
    const exp = e.target.closest('[data-export-url]');
    if (exp) {
      e.preventDefault();
      let url = exp.dataset.exportUrl;
      // Modo demo: si no hay endpoint real, sólo avisamos
      if (url === '#' || !url) {
        App.toast('En la versión final se descargaría el Excel de esta clase.', 'info', { title: 'Demo' });
        return;
      }
      exportExcel(url, exp);
    }

    const send = e.target.closest('[data-send-url]');
    if (send) {
      e.preventDefault();
      const url = send.dataset.sendUrl;
      const id = send.dataset.claseId;
      if (url === '#' || !url) {
        App.toast('En la versión final se enviaría el reporte a secretaría.', 'info', { title: 'Demo' });
        send.innerHTML = '<i class="fa-solid fa-check"></i> Enviado';
        send.classList.remove('btn-ghost');
        send.classList.add('btn-success');
        send.disabled = true;
        return;
      }
      enviarSecretaria(url, id, send);
    }
  });

  // Export público por si se necesita invocar manualmente
  window.Export = { exportExcel, enviarSecretaria };
})();
