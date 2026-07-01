  <section class="section section-contacto">
    <div class="container">
      <div class="section-head">
        <span class="eyebrow">Soporte</span>
        <h2>¿Encontraste un error en la página?</h2>
        <p>
          Esta sección es para reportar problemas o errores del sistema. Escribinos por
          el formulario o contactá directamente a cualquier integrante de nuestro equipo.
        </p>
      </div>

      <div class="contact-layout">
        <!-- Formulario de reporte -->
        <div class="contact-panel">
          <h3>Reportar un error</h3>
          <p>Contanos qué pasó y te respondemos a la brevedad.</p>
          <form id="reportForm" novalidate>
            <div class="field">
              <label for="nombre">Nombre</label>
              <div class="input-group">
                <i class="input-icon fa-solid fa-user"></i>
                <input class="input" type="text" id="nombre" name="nombre" placeholder="Tu nombre" required />
              </div>
            </div>
            <div class="field">
              <label for="email">Email</label>
              <div class="input-group">
                <i class="input-icon fa-solid fa-envelope"></i>
                <input class="input" type="email" id="email" name="email" placeholder="tucorreo@ejemplo.com" required />
              </div>
            </div>
            <div class="field">
              <label for="asunto">Asunto</label>
              <div class="input-group">
                <i class="input-icon fa-solid fa-tag"></i>
                <input class="input" type="text" id="asunto" name="asunto" placeholder="Ej: No carga el QR" required />
              </div>
            </div>
            <div class="field">
              <label for="mensaje">Descripción del error</label>
              <textarea class="textarea" id="mensaje" name="mensaje" placeholder="Describí qué error encontraste, en qué pantalla y qué estabas haciendo." required></textarea>
            </div>
            <button type="submit" class="btn btn-primary btn-block btn-lg">
              <i class="fa-solid fa-paper-plane"></i> Enviar reporte
            </button>
          </form>
        </div>

        <!-- Contacto directo -->
        <div class="contact-panel">
          <h3>Contacto directo</h3>
          <p>También podés escribirnos por estos medios.</p>
          <div class="team-contact-list">
            <a class="team-contact" href="mailto:asistenciaqrsoporte@gmail.com">
              <div class="ico-circle ico-mail"><i class="fa-solid fa-envelope"></i></div>
              <div class="info">
                <strong>Gmail</strong>
                <span>asistenciaqrsoporte@gmail.com</span>
              </div>
            </a>
            <a class="team-contact" href="tel:+541138493537">
              <div class="ico-circle ico-phone"><i class="fa-solid fa-phone"></i></div>
              <div class="info">
                <strong>Teléfono</strong>
                <span>+54 11 3849-3537</span>
              </div>
            </a>
            <!--
            <a class="team-contact" href="https://instagram.com/asistenciaqr" target="_blank" rel="noopener">
              <div class="ico-circle ico-ig"><i class="fa-brands fa-instagram"></i></div>
              <div class="info">
                <strong>Instagram</strong>
                <span>@asistenciaqr</span>
              </div>
            </a>-->
          </div>
        </div>
      </div>
    </div>
  </section>

  <script src="assets/js/utils.js"></script>
  <script>
    document.getElementById('reportForm').addEventListener('submit', async function (e) {
      e.preventDefault();
      const form = this;

      if (!form.checkValidity()) {
        if (window.App && App.toast) App.toast('Completá todos los campos.', 'error');
        else alert('Completá todos los campos.');
        return;
      }

      const submitBtn = form.querySelector('button[type="submit"]');
      const originalHtml = submitBtn.innerHTML;
      submitBtn.disabled = true;
      submitBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Enviando...';

      const payload = {
        nombre: form.nombre.value.trim(),
        email: form.email.value.trim(),
        asunto: form.asunto.value.trim(),
        mensaje: form.mensaje.value.trim(),
        _subject: 'Reporte: ' + form.asunto.value.trim(),
      };

      try {
        const res = await fetch('https://formspree.io/f/xpqgjeoy', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
          body: JSON.stringify(payload),
        });

        if (res.ok) {
          if (window.App && App.toast) App.toast('¡Gracias! Recibimos tu reporte.', 'success');
          else alert('¡Gracias! Recibimos tu reporte.');
          form.reset();
        } else {
          const data = await res.json().catch(() => ({}));
          const msg = (data.errors && data.errors.map(e => e.message).join(', ')) || 'No se pudo enviar el reporte. Intentá de nuevo.';
          if (window.App && App.toast) App.toast(msg, 'error');
          else alert(msg);
        }
      } catch (err) {
        const msg = 'Error de conexión. Intentá de nuevo.';
        if (window.App && App.toast) App.toast(msg, 'error');
        else alert(msg);
      } finally {
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalHtml;
      }
    });
  </script>
