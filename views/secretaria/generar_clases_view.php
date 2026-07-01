<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" /><meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Generar clases · Secretaría</title>
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
  <link rel="stylesheet" href="../assets/css/main.css" />
  <link rel="stylesheet" href="../assets/css/dashboard.css" />
</head>
<body>
<div class="app-layout">
  <aside class="sidebar">
    <div class="sidebar-brand">
      <img src="../assets/img/logo.png" alt="Logo" />
      <div><div class="name">Asistencia QR</div><div class="sub">Secretaría</div></div>
    </div>
    <nav class="sidebar-nav">
      <span class="nav-label">Gestión</span>
      <a href="exportar.php"><i class="fa-solid fa-file-export"></i> Clases del período</a>
      <a href="materias.php" class="active"><i class="fa-solid fa-book"></i> Materias</a>
      <a href="aulas.php"><i class="fa-solid fa-door-open"></i> Aulas</a>
      <a href="inscripciones.php"><i class="fa-solid fa-user-plus"></i> Inscripciones</a>
      <a href="usuarios.php"><i class="fa-solid fa-users"></i> Alta de usuarios</a>
      <a href="reportes.php"><i class="fa-solid fa-chart-pie"></i> Reportes</a>
      <span class="nav-label">Cuenta</span>
      <a href="perfil.php"><i class="fa-solid fa-user"></i> Mi perfil</a>
      <a href="../logout.php"><i class="fa-solid fa-right-from-bracket"></i> Cerrar sesión</a>
    </nav>
    <div class="sidebar-user">
      <div class="avatar"><?= htmlspecialchars($iniciales) ?></div>
      <div class="meta">
        <div class="u-name"><?= htmlspecialchars($_SESSION['nombre']) ?></div>
        <div class="u-role">Secretaría</div>
      </div>
      <a href="../logout.php" class="logout"><i class="fa-solid fa-right-from-bracket"></i></a>
    </div>
  </aside>
  <div class="sidebar-backdrop" data-sidebar-backdrop></div>

  <div class="app-main">
    <header class="topbar">
      <button class="hamburger" data-sidebar-toggle aria-label="Menú"><i class="fa-solid fa-bars"></i></button>
      <div class="page-title">
        Generar clases
        <small><?= htmlspecialchars($materia['nombre'].' · '.$materia['curso']) ?></small>
      </div>
      <div class="topbar-right">
        <a href="materias.php" class="btn btn-ghost btn-sm">
          <i class="fa-solid fa-arrow-left"></i> Volver
        </a>
      </div>
    </header>

    <main class="app-content" style="max-width:640px">

      <!-- Info de la materia -->
      <div class="card" style="padding:18px 22px;margin-bottom:20px;display:flex;gap:16px;align-items:center">
        <div class="stat-icon i-blue" style="flex-shrink:0"><i class="fa-solid fa-book"></i></div>
        <div>
          <div style="font-weight:700;font-size:1rem"><?= htmlspecialchars($materia['nombre']) ?></div>
          <div class="text-muted" style="font-size:0.85rem">
            <?= htmlspecialchars($materia['curso']) ?> ·
            Prof. <?= htmlspecialchars($materia['profesor']) ?> ·
            <span class="badge <?= $materia['modalidad']==='virtual'?'badge-muted':'badge-accent' ?>"><?= ucfirst($materia['modalidad']) ?></span>
          </div>
        </div>
      </div>

      <?php if (empty($horarios)): ?>
        <div class="card" style="padding:32px;text-align:center;color:var(--c-text-faint)">
          <i class="fa-solid fa-calendar-xmark" style="font-size:2rem;margin-bottom:12px"></i>
          <p>Esta materia no tiene horario semanal configurado.</p>
          <a href="materias.php" class="btn btn-primary mt-3">Ir a Materias para configurarlo</a>
        </div>
      <?php else: ?>

      <form id="generarForm">
        <input type="hidden" name="materia_id" value="<?= $materia_id ?>" />

        <!-- Selección de horarios -->
        <div class="card" style="padding:22px;margin-bottom:18px">
          <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:4px">
            <h3 style="font-size:1rem">Horarios a generar</h3>
            <button type="button" id="btnAgregarHorario" class="btn btn-ghost btn-sm">
              <i class="fa-solid fa-plus"></i> Agregar día
            </button>
          </div>
          <p class="text-muted" style="font-size:0.85rem;margin-bottom:16px">
            Seleccioná los horarios a incluir. Podés editarlos o agregar días nuevos.
          </p>
          <div id="horariosLista" style="display:flex;flex-direction:column;gap:10px">
            <?php foreach ($horarios as $h): ?>
            <div class="horario-item" data-id="<?= $h['id'] ?>"
                 style="border:2px solid var(--c-primary);background:var(--c-primary-soft,#eff6ff);
                        border-radius:10px;padding:12px 16px;transition:.15s">
              <!-- Vista -->
              <div class="horario-view" style="display:flex;align-items:center;gap:12px">
                <input type="checkbox" name="horario_ids[]" value="<?= $h['id'] ?>"
                       style="width:18px;height:18px;accent-color:var(--c-primary);flex-shrink:0" checked />
                <div style="flex:1">
                  <div style="font-weight:600"><?= $nombres_dia[$h['dia_semana']] ?></div>
                  <div class="text-muted" style="font-size:0.83rem">
                    <?= substr($h['hora_inicio'],0,5) ?> — <?= substr($h['hora_fin'],0,5) ?>
                  </div>
                </div>
                <button type="button" class="btn btn-ghost btn-sm btn-edit-h" title="Editar horario">
                  <i class="fa-solid fa-pen"></i>
                </button>
                <button type="button" class="btn btn-ghost btn-sm btn-del-h"
                        title="Eliminar horario" style="color:var(--c-danger)">
                  <i class="fa-solid fa-trash"></i>
                </button>
              </div>
              <!-- Edición inline (oculta) -->
              <div class="horario-edit" style="display:none;flex-direction:column;gap:10px;margin-top:12px">
                <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:10px">
                  <div class="field" style="margin:0">
                    <label style="font-size:0.78rem">Día</label>
                    <select class="select edit-dia" style="padding:8px 10px;font-size:0.88rem">
                      <?php foreach ($nombres_dia as $num => $ndm): ?>
                      <option value="<?= $num ?>" <?= $h['dia_semana'] == $num ? 'selected' : '' ?>><?= $ndm ?></option>
                      <?php endforeach; ?>
                    </select>
                  </div>
                  <div class="field" style="margin:0">
                    <label style="font-size:0.78rem">Hora inicio</label>
                    <input class="input edit-inicio" type="time"
                           value="<?= substr($h['hora_inicio'],0,5) ?>"
                           style="padding:8px 10px;font-size:0.88rem" />
                  </div>
                  <div class="field" style="margin:0">
                    <label style="font-size:0.78rem">Hora fin</label>
                    <input class="input edit-fin" type="time"
                           value="<?= substr($h['hora_fin'],0,5) ?>"
                           style="padding:8px 10px;font-size:0.88rem" />
                  </div>
                </div>
                <div style="display:flex;gap:8px;justify-content:flex-end">
                  <button type="button" class="btn btn-ghost btn-sm btn-cancel-edit">Cancelar</button>
                  <button type="button" class="btn btn-primary btn-sm btn-save-h">
                    <i class="fa-solid fa-check"></i> Guardar
                  </button>
                </div>
              </div>
            </div>
            <?php endforeach; ?>

            <!-- Formulario nuevo horario (oculto) -->
            <div id="nuevoHorarioForm" style="display:none;border:2px dashed var(--c-border);
                 border-radius:10px;padding:14px 16px">
              <div style="font-weight:600;margin-bottom:10px;font-size:0.9rem">Nuevo horario</div>
              <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:10px">
                <div class="field" style="margin:0">
                  <label style="font-size:0.78rem">Día</label>
                  <select class="select" id="nuevoDia" style="padding:8px 10px;font-size:0.88rem">
                    <?php foreach ($nombres_dia as $num => $ndm): ?>
                    <option value="<?= $num ?>"><?= $ndm ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div class="field" style="margin:0">
                  <label style="font-size:0.78rem">Hora inicio</label>
                  <input class="input" type="time" id="nuevoInicio"
                         style="padding:8px 10px;font-size:0.88rem" />
                </div>
                <div class="field" style="margin:0">
                  <label style="font-size:0.78rem">Hora fin</label>
                  <input class="input" type="time" id="nuevoFin"
                         style="padding:8px 10px;font-size:0.88rem" />
                </div>
              </div>
              <div style="display:flex;gap:8px;justify-content:flex-end;margin-top:10px">
                <button type="button" class="btn btn-ghost btn-sm" id="btnCancelarNuevo">Cancelar</button>
                <button type="button" class="btn btn-primary btn-sm" id="btnGuardarNuevo">
                  <i class="fa-solid fa-plus"></i> Agregar
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Rango de fechas -->
        <div class="card" style="padding:22px;margin-bottom:18px">
          <h3 style="font-size:1rem;margin-bottom:16px">Rango del cuatrimestre</h3>
          <div class="form-grid">
            <div class="field">
              <label>Fecha de inicio</label>
              <input class="input" type="date" name="fecha_inicio" id="fechaInicio" required />
            </div>
            <div class="field">
              <label>Fecha de fin</label>
              <input class="input" type="date" name="fecha_fin" id="fechaFin" required />
            </div>
          </div>
          <div id="preview" class="text-muted" style="font-size:0.85rem;margin-top:8px"></div>
        </div>

        <button type="submit" class="btn btn-primary btn-lg btn-block">
          <i class="fa-solid fa-calendar-plus"></i> Generar clases
        </button>
      </form>

      <?php endif; ?>

    </main>
  </div>
</div>

<!-- Resultado -->
<div id="resultCard" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.4);
     z-index:200;display:none;align-items:center;justify-content:center">
  <div class="card" style="padding:32px;max-width:400px;width:90%;text-align:center">
    <div class="stat-icon i-green" style="margin:0 auto 16px"><i class="fa-solid fa-circle-check"></i></div>
    <h2 style="font-size:1.2rem;margin-bottom:8px" id="resTitle"></h2>
    <p class="text-muted" id="resBody" style="font-size:0.9rem;margin-bottom:20px"></p>
    <div style="display:flex;gap:10px;justify-content:center">
      <a href="exportar.php" class="btn btn-primary">Ver clases</a>
      <a href="materias.php" class="btn btn-ghost">Volver</a>
    </div>
  </div>
</div>

<script src="../assets/js/utils.js"></script>
<script>
  const MATERIA_ID = <?= $materia_id ?>;
  const NOMBRES_DIA = <?= json_encode($nombres_dia) ?>;

  // ── Mapa dia_semana por horario_id (se actualiza al editar) ──
  const diasMap = {};
  <?php foreach ($horarios as $h): ?>
  diasMap[<?= $h['id'] ?>] = <?= $h['dia_semana'] ?>;
  <?php endforeach; ?>

  // ── Preview de cantidad de clases ────────────────────────────
  function actualizarPreview() {
    const fi  = document.getElementById('fechaInicio').value;
    const ff  = document.getElementById('fechaFin').value;
    const ids = [...document.querySelectorAll('input[name="horario_ids[]"]:checked')].map(c => parseInt(c.value));
    const preview = document.getElementById('preview');
    if (!fi || !ff || fi > ff || !ids.length) { preview.textContent = ''; return; }

    const diasSel = ids.map(id => diasMap[id]).filter(Boolean);
    let count = 0;
    const d   = new Date(fi + 'T00:00:00');
    const fin = new Date(ff + 'T00:00:00');
    while (d <= fin) {
      const dow = d.getDay() === 0 ? 7 : d.getDay();
      if (diasSel.includes(dow)) count++;
      d.setDate(d.getDate() + 1);
    }
    preview.innerHTML = '<i class="fa-solid fa-circle-info"></i> Se generarán aproximadamente <strong>' + count + ' clase(s)</strong>.';
  }

  document.getElementById('fechaInicio').addEventListener('change', actualizarPreview);
  document.getElementById('fechaFin').addEventListener('change', actualizarPreview);

  // ── Helpers de ítem horario ──────────────────────────────────
  function bindItem(item) {
    const cb      = item.querySelector('input[type=checkbox]');
    const view    = item.querySelector('.horario-view');
    const edit    = item.querySelector('.horario-edit');
    const btnEdit = item.querySelector('.btn-edit-h');
    const btnDel  = item.querySelector('.btn-del-h');
    const btnSave = item.querySelector('.btn-save-h');
    const btnCanc = item.querySelector('.btn-cancel-edit');

    // Selección visual
    function updateBorder() {
      item.style.borderColor = cb.checked ? 'var(--c-primary)' : 'var(--c-border)';
      item.style.background  = cb.checked ? 'var(--c-primary-soft,#eff6ff)' : '';
    }
    cb.addEventListener('change', function () { updateBorder(); actualizarPreview(); });
    updateBorder();

    // Abrir edición
    btnEdit.addEventListener('click', function () {
      view.style.display = 'none';
      edit.style.display = 'flex';
    });
    btnCanc.addEventListener('click', function () {
      edit.style.display = 'none';
      view.style.display = 'flex';
    });

    // Guardar edición
    btnSave.addEventListener('click', function () {
      const id    = parseInt(item.dataset.id);
      const dia   = parseInt(item.querySelector('.edit-dia').value);
      const ini   = item.querySelector('.edit-inicio').value;
      const fin   = item.querySelector('.edit-fin').value;
      if (!ini || !fin) { App.toast('Completá los horarios.', 'error'); return; }

      App.api('../api/actualizar_horario.php', {
        method: 'POST', loader: true,
        body: JSON.stringify({ accion: 'actualizar', id, dia_semana: dia, hora_inicio: ini, hora_fin: fin }),
      })
      .then(function () {
        // Actualizar vista
        diasMap[id] = dia;
        item.querySelector('.horario-view div div:first-child').textContent = NOMBRES_DIA[dia];
        item.querySelector('.horario-view div div:last-child').textContent  = ini + ' — ' + fin;
        edit.style.display = 'none';
        view.style.display = 'flex';
        actualizarPreview();
        App.toast('Horario actualizado.', 'success');
      })
      .catch(err => App.toast(err.message, 'error'));
    });

    // Eliminar
    btnDel.addEventListener('click', function () {
      if (!confirm('¿Eliminás este horario?')) return;
      const id = parseInt(item.dataset.id);
      App.api('../api/actualizar_horario.php', {
        method: 'POST', loader: true,
        body: JSON.stringify({ accion: 'eliminar', id }),
      })
      .then(function () {
        delete diasMap[id];
        item.remove();
        actualizarPreview();
        App.toast('Horario eliminado.', 'success');
      })
      .catch(err => App.toast(err.message, 'error'));
    });
  }

  // Inicializar ítems existentes
  document.querySelectorAll('.horario-item').forEach(bindItem);
  document.querySelectorAll('input[name="horario_ids[]"]').forEach(c =>
    c.addEventListener('change', actualizarPreview)
  );

  // ── Agregar nuevo horario ────────────────────────────────────
  const nuevoForm = document.getElementById('nuevoHorarioForm');

  document.getElementById('btnAgregarHorario').addEventListener('click', function () {
    nuevoForm.style.display = 'block';
    this.style.display = 'none';
  });
  document.getElementById('btnCancelarNuevo').addEventListener('click', function () {
    nuevoForm.style.display = 'none';
    document.getElementById('btnAgregarHorario').style.display = '';
  });
  document.getElementById('btnGuardarNuevo').addEventListener('click', function () {
    const dia = parseInt(document.getElementById('nuevoDia').value);
    const ini = document.getElementById('nuevoInicio').value;
    const fin = document.getElementById('nuevoFin').value;
    if (!ini || !fin) { App.toast('Completá los horarios.', 'error'); return; }

    App.api('../api/actualizar_horario.php', {
      method: 'POST', loader: true,
      body: JSON.stringify({ accion: 'crear', materia_id: MATERIA_ID, dia_semana: dia, hora_inicio: ini, hora_fin: fin }),
    })
    .then(function (res) {
      // Crear nuevo ítem en el DOM
      const div = document.createElement('div');
      div.className = 'horario-item';
      div.dataset.id = res.id;
      div.style.cssText = 'border:2px solid var(--c-primary);background:var(--c-primary-soft,#eff6ff);border-radius:10px;padding:12px 16px;transition:.15s';
      div.innerHTML = `
        <div class="horario-view" style="display:flex;align-items:center;gap:12px">
          <input type="checkbox" name="horario_ids[]" value="${res.id}"
                 style="width:18px;height:18px;accent-color:var(--c-primary);flex-shrink:0" checked />
          <div style="flex:1">
            <div style="font-weight:600">${NOMBRES_DIA[dia]}</div>
            <div class="text-muted" style="font-size:0.83rem">${ini} — ${fin}</div>
          </div>
          <button type="button" class="btn btn-ghost btn-sm btn-edit-h" title="Editar horario">
            <i class="fa-solid fa-pen"></i>
          </button>
          <button type="button" class="btn btn-ghost btn-sm btn-del-h" title="Eliminar horario" style="color:var(--c-danger)">
            <i class="fa-solid fa-trash"></i>
          </button>
        </div>
        <div class="horario-edit" style="display:none;flex-direction:column;gap:10px;margin-top:12px">
          <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:10px">
            <div class="field" style="margin:0">
              <label style="font-size:0.78rem">Día</label>
              <select class="select edit-dia" style="padding:8px 10px;font-size:0.88rem">
                ${Object.entries(NOMBRES_DIA).map(([n,nm]) =>
                  `<option value="${n}" ${parseInt(n)===dia?'selected':''}>${nm}</option>`).join('')}
              </select>
            </div>
            <div class="field" style="margin:0">
              <label style="font-size:0.78rem">Hora inicio</label>
              <input class="input edit-inicio" type="time" value="${ini}" style="padding:8px 10px;font-size:0.88rem" />
            </div>
            <div class="field" style="margin:0">
              <label style="font-size:0.78rem">Hora fin</label>
              <input class="input edit-fin" type="time" value="${fin}" style="padding:8px 10px;font-size:0.88rem" />
            </div>
          </div>
          <div style="display:flex;gap:8px;justify-content:flex-end">
            <button type="button" class="btn btn-ghost btn-sm btn-cancel-edit">Cancelar</button>
            <button type="button" class="btn btn-primary btn-sm btn-save-h">
              <i class="fa-solid fa-check"></i> Guardar
            </button>
          </div>
        </div>`;

      diasMap[res.id] = dia;
      document.getElementById('horariosLista').insertBefore(div, nuevoForm);
      bindItem(div);

      // Reset formulario
      nuevoForm.style.display = 'none';
      document.getElementById('btnAgregarHorario').style.display = '';
      document.getElementById('nuevoInicio').value = '';
      document.getElementById('nuevoFin').value = '';
      actualizarPreview();
      App.toast('Horario agregado.', 'success');
    })
    .catch(err => App.toast(err.message, 'error'));
  });

  // ── Generar clases ───────────────────────────────────────────
  document.getElementById('generarForm').addEventListener('submit', function (e) {
    e.preventDefault();
    const fd  = new FormData(this);
    const ids = fd.getAll('horario_ids[]').map(Number);
    if (!ids.length) { App.toast('Seleccioná al menos un horario.', 'error'); return; }

    App.api('../api/generar_clases.php', {
      method: 'POST', loader: true,
      body: JSON.stringify({
        materia_id:   parseInt(fd.get('materia_id')),
        horario_ids:  ids,
        fecha_inicio: fd.get('fecha_inicio'),
        fecha_fin:    fd.get('fecha_fin'),
      }),
    })
    .then(function (res) {
      document.getElementById('resTitle').textContent = res.generadas + ' clase(s) generadas';
      document.getElementById('resBody').textContent =
        (res.saltadas ? res.saltadas + ' ya existían y fueron omitidas.' : 'Todas son nuevas.');
      document.getElementById('resultCard').style.display = 'flex';
    })
    .catch(err => App.toast(err.message, 'error'));
  });
</script>
</body>
</html>
