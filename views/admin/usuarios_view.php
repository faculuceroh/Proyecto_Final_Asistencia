<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" /><meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Usuarios &middot; Admin</title>
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="../assets/vendor/fontawesome/css/all.min.css" />
  <link rel="stylesheet" href="../assets/css/main.css" />
  <link rel="stylesheet" href="../assets/css/dashboard.css" />
</head>
<body>
<div class="app-layout role-admin">
  <aside class="sidebar">
    <div class="sidebar-brand">
      <img src="../assets/img/logo-dashboard.png" alt="Logo" />
      <div><div class="name">Asistencia QR</div><div class="sub">Administraci&oacute;n</div></div>
    </div>
    <nav class="sidebar-nav">
      <span class="nav-label">Sistema</span>
      <a href="dashboard.php"><i class="fa-solid fa-gauge-high"></i> Resumen</a>
      <a href="usuarios.php" class="active"><i class="fa-solid fa-users"></i> Usuarios</a>
      <a href="materias.php"><i class="fa-solid fa-book"></i> Materias</a>
      <a href="aulas.php"><i class="fa-solid fa-door-open"></i> Aulas</a>
      <a href="configuracion.php"><i class="fa-solid fa-gear"></i> Configuraci&oacute;n</a>
      <span class="nav-label">Cuenta</span>
      <a href="../logout.php"><i class="fa-solid fa-right-from-bracket"></i> Cerrar sesi&oacute;n</a>
    </nav>
    <div class="sidebar-user">
      <div class="avatar"><?= htmlspecialchars($iniciales) ?></div>
      <div class="meta">
        <div class="u-name"><?= htmlspecialchars($_SESSION['nombre']) ?></div>
        <div class="u-role">Administrador</div>
      </div>
      <a href="../logout.php" class="logout"><i class="fa-solid fa-right-from-bracket"></i></a>
    </div>
  </aside>
  <div class="sidebar-backdrop" data-sidebar-backdrop></div>

  <div class="app-main">
    <header class="topbar">
      <button class="hamburger" data-sidebar-toggle aria-label="Menu"><i class="fa-solid fa-bars"></i></button>
      <div class="page-title">Usuarios <small>Alta y gesti&oacute;n de cuentas</small></div>
    </header>
    <main class="app-content">

      <div style="display:grid;grid-template-columns:1.4fr 1fr;gap:20px;align-items:start;margin-bottom:28px">

        <!-- Formulario alta -->
        <div class="card" style="padding:24px">
          <h3 style="font-size:1.1rem;margin-bottom:4px">Nuevo usuario</h3>
          <p class="text-muted" style="font-size:0.88rem;margin-bottom:18px">Eleg&iacute; el tipo y complet&aacute; los datos.</p>

          <div class="type-selector" role="radiogroup">
            <label class="type-option">
              <input type="radio" name="tipo" value="alumno" checked />
              <span class="tile"><i class="fa-solid fa-user-graduate"></i> Alumno</span>
            </label>
            <label class="type-option">
              <input type="radio" name="tipo" value="profesor" />
              <span class="tile"><i class="fa-solid fa-chalkboard-user"></i> Profesor</span>
            </label>
            <label class="type-option">
              <input type="radio" name="tipo" value="secretaria" />
              <span class="tile"><i class="fa-solid fa-folder-open"></i> Secretar&iacute;a</span>
            </label>
          </div>

          <form id="userForm">
            <div class="form-grid">
              <div class="field"><label>Nombre</label><input class="input" name="nombre" placeholder="Ej: Juan" required /></div>
              <div class="field"><label>Apellido</label><input class="input" name="apellido" placeholder="Ej: Perez" required /></div>
              <div class="field"><label>Legajo</label><input class="input" name="legajo" placeholder="Ej: 20460" inputmode="numeric" required /></div>
              <div class="field"><label>Email</label><input class="input" type="email" name="email" placeholder="nombre@instituto.edu" /></div>
              <div class="field campo-alumno full">
                <label>Curso</label>
                <select class="select" name="curso">
                  <?php foreach ($cursos as $c): ?>
                    <option><?= htmlspecialchars($c) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
            </div>
            <button type="submit" class="btn btn-primary btn-block mt-2">
              <i class="fa-solid fa-user-plus"></i> <span id="submitLabel">Agregar alumno</span>
            </button>
          </form>
        </div>

        <!-- Carga masiva -->
        <div class="card" style="padding:24px">
          <h3 style="font-size:1.1rem;margin-bottom:4px">Carga masiva</h3>
          <p class="text-muted" style="font-size:0.88rem;margin-bottom:16px">Import&aacute; varios alumnos desde un Excel.</p>
          <label class="card" style="display:block;border:2px dashed var(--c-border);background:var(--c-bg);padding:24px;text-align:center;cursor:pointer;border-radius:14px">
            <i class="fa-solid fa-file-excel" style="font-size:1.8rem;color:var(--c-success)"></i>
            <div style="font-weight:700;margin-top:8px">Subir archivo Excel</div>
            <div class="text-muted" style="font-size:0.82rem">.xlsx o .csv &mdash; arrastr&aacute; o hac&eacute; clic</div>
            <input type="file" accept=".xlsx,.csv" hidden id="fileInput" />
          </label>
          <div id="fileName" class="text-muted mt-1" style="font-size:0.84rem"></div>
          <div class="card" style="background:var(--c-muted-soft);border:none;padding:14px;margin-top:18px;font-size:0.82rem;color:var(--c-text-soft)">
            <strong style="color:var(--c-text)">Formato esperado (5 columnas):</strong><br>
            <span style="font-family:monospace;display:inline-block;margin-top:6px">Nombre &middot; Apellido &middot; Legajo &middot; Curso &middot; Email</span><br><br>
            La contrase&ntilde;a inicial de cada alumno es su legajo.
          </div>
        </div>

      </div>

      <!-- Listado -->
      <div class="card table-card">
        <div class="toolbar" style="padding:16px 16px 0">
          <h3 style="font-size:1rem">Todos los usuarios</h3>
          <div style="display:flex;gap:8px;align-items:center">
            <select class="select" id="filtroEstado" style="height:38px;font-size:0.86rem;padding:0 12px;min-width:120px;margin:0">
              <option value="1" selected>Activos</option>
              <option value="0">Inactivos</option>
              <option value="">Todos los estados</option>
            </select>
            <select class="select" id="filtroRol" style="height:38px;font-size:0.86rem;padding:0 12px;min-width:140px;margin:0">
              <option value="">Todos los roles</option>
              <option value="alumno">Alumnos</option>
              <option value="profesor">Profesores</option>
              <option value="secretaria">Secretaría</option>
              <option value="admin">Admin</option>
            </select>
            <input class="input" id="filtroBuscar" placeholder="Buscar por nombre o legajo..." style="min-width:240px;margin:0" />
          </div>
        </div>
        <div class="table-scroll">
          <table class="data-table">
            <thead><tr><th>Nombre</th><th>Legajo</th><th>Curso</th><th>Rol</th><th>Estado</th><th></th></tr></thead>
            <tbody id="usuariosBody"></tbody>
          </table>
        </div>
        <div class="pagination" id="paginacion">
          <span class="pg-info" id="pgInfo"></span>
          <div class="pg-controls" id="pgBtns"></div>
        </div>
      </div>

    </main>
  </div>
</div>

<!-- Modal: editar usuario -->
<div class="modal-overlay hidden" id="modalEditar">
  <div class="modal">
    <div class="modal-head">
      <h3>Editar usuario</h3>
      <button class="modal-close" id="closeModalEditar">&times;</button>
    </div>
    <form id="editForm">
      <div class="modal-body">
        <input type="hidden" name="id" />
        <div class="form-grid">
          <div class="field"><label>Nombre</label><input class="input" name="nombre" required /></div>
          <div class="field"><label>Apellido</label><input class="input" name="apellido" required /></div>
          <div class="field"><label>Legajo</label><input class="input" name="legajo" inputmode="numeric" required /></div>
          <div class="field"><label>Email</label><input class="input" type="email" name="email" /></div>
          <div class="field full" id="campoCursoEditar">
            <label>Curso</label>
            <select class="select" name="curso">
              <?php foreach ($cursos as $c): ?>
                <option><?= htmlspecialchars($c) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
      </div>
      <div class="modal-foot">
        <button type="button" class="btn btn-ghost" id="cancelEditar">Cancelar</button>
        <button type="submit" class="btn btn-primary"><i class="fa-solid fa-floppy-disk"></i> Guardar cambios</button>
      </div>
    </form>
  </div>
</div>

<script src="../assets/js/utils.js"></script>
<script>
  const labels = { alumno:'Agregar alumno', profesor:'Agregar profesor', secretaria:'Agregar secretaria' };

  function setTipo(tipo) {
    App.qsa('.campo-alumno').forEach(el => el.classList.toggle('hidden', tipo !== 'alumno'));
    App.qs('#submitLabel').textContent = labels[tipo] || 'Agregar';
  }
  App.qsa('input[name="tipo"]').forEach(r => r.addEventListener('change', () => setTipo(r.value)));

  App.qs('#userForm').addEventListener('submit', function (e) {
    e.preventDefault();
    const tipo = App.qs('input[name="tipo"]:checked').value;
    const data = Object.fromEntries(new FormData(this));
    App.api('../api/crear_usuario.php', {
      method: 'POST', loader: true,
      body: JSON.stringify({ tipo, ...data }),
    })
    .then(function () {
      App.toast('Usuario creado correctamente.', 'success');
      App.qs('#userForm').reset();
      App.qs('input[name="tipo"][value="alumno"]').checked = true;
      setTipo('alumno');
      setTimeout(() => location.reload(), 1200);
    })
    .catch(err => App.toast(err.message, 'error'));
  });

  App.qs('#fileInput').addEventListener('change', function () {
    if (!this.files[0]) return;
    App.qs('#fileName').textContent = this.files[0].name;
    const form = new FormData();
    form.append('archivo', this.files[0]);
    App.showLoader && App.showLoader();
    fetch('../api/importar_alumnos.php', { method: 'POST', body: form })
      .then(r => r.json())
      .then(function (d) {
        App.hideLoader && App.hideLoader();
        App.toast('Importados: ' + d.creados + ' alumnos.', d.creados > 0 ? 'success' : 'error');
        if (d.creados > 0) setTimeout(() => location.reload(), 1200);
      })
      .catch(function () { App.hideLoader && App.hideLoader(); App.toast('Error al importar.', 'error'); });
  });

  // ── Listado: filtro y paginación en el navegador (sin recargar) ─────────
  const ALL = <?= json_encode(array_values($usuarios)) ?>;
  const PER_PAGE = 5;
  const BADGE_ROL = { alumno:'badge-accent', profesor:'badge-muted', secretaria:'badge-warning', admin:'badge-danger' };
  const LABEL_ROL = { alumno:'Alumno', profesor:'Profesor', secretaria:'Secretaria', admin:'Admin' };

  let filtrado = [];
  let pagina   = 1;

  function escU(s) {
    return String(s ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
  }

  function aplicarFiltros() {
    const estado = App.qs('#filtroEstado').value;
    const rol    = App.qs('#filtroRol').value;
    const q      = App.qs('#filtroBuscar').value.toLowerCase().trim();

    filtrado = ALL.filter(u => {
      if (estado !== '' && String(u.activo) !== estado) return false;
      if (rol !== '' && u.rol !== rol) return false;
      if (q && !(u.nombre.toLowerCase().includes(q) || u.apellido.toLowerCase().includes(q) || u.legajo.toLowerCase().includes(q))) return false;
      return true;
    });
    pagina = 1;
    render();
  }

  function rowHtmlU(u) {
    return `
      <tr>
        <td>${escU(u.apellido)}, ${escU(u.nombre)}</td>
        <td>${escU(u.legajo)}</td>
        <td>${escU(u.curso || '—')}</td>
        <td><span class="badge ${BADGE_ROL[u.rol] || 'badge-muted'}">${LABEL_ROL[u.rol] || escU(u.rol)}</span></td>
        <td><span class="badge ${u.activo == 1 ? 'badge-success' : 'badge-danger'}">${u.activo == 1 ? 'Activo' : 'Inactivo'}</span></td>
        <td style="white-space:nowrap">
          <button type="button" class="btn btn-ghost btn-sm" data-editar="${u.id}" title="Editar usuario">
            <i class="fa-solid fa-pen-to-square"></i>
          </button>
        </td>
      </tr>`;
  }

  function render() {
    const tbody  = App.qs('#usuariosBody');
    const inicio = (pagina - 1) * PER_PAGE;
    const items  = filtrado.slice(inicio, inicio + PER_PAGE);

    tbody.innerHTML = items.length
      ? items.map(rowHtmlU).join('')
      : '<tr><td colspan="6" style="text-align:center;padding:32px;color:var(--c-text-faint)">No se encontraron usuarios.</td></tr>';

    tbody.querySelectorAll('[data-editar]').forEach(btn => {
      btn.addEventListener('click', () => abrirEditar(btn.dataset.editar));
    });

    renderPaginacionU(inicio, items.length);
  }

  // ── Editar usuario ───────────────────────────────────────────────────
  const modalEditar = App.qs('#modalEditar');
  const editForm     = App.qs('#editForm');

  function abrirEditar(id) {
    const u = ALL.find(u => String(u.id) === String(id));
    if (!u) return;
    editForm.id.value       = u.id;
    editForm.nombre.value   = u.nombre;
    editForm.apellido.value = u.apellido;
    editForm.legajo.value   = u.legajo;
    editForm.email.value    = u.email || '';
    App.qs('#campoCursoEditar').classList.toggle('hidden', u.rol !== 'alumno');
    if (u.rol === 'alumno') editForm.curso.value = u.curso || '';
    modalEditar.classList.remove('hidden');
  }

  function cerrarEditar() { modalEditar.classList.add('hidden'); }

  App.qs('#closeModalEditar').addEventListener('click', cerrarEditar);
  App.qs('#cancelEditar').addEventListener('click', cerrarEditar);
  modalEditar.addEventListener('click', e => { if (e.target === modalEditar) cerrarEditar(); });

  editForm.addEventListener('submit', function (e) {
    e.preventDefault();
    const data = Object.fromEntries(new FormData(this));
    App.api('../api/editar_usuario.php', {
      method: 'POST', loader: true,
      body: JSON.stringify(data),
    })
    .then(function () {
      const u = ALL.find(u => String(u.id) === String(data.id));
      if (u) {
        u.nombre = data.nombre; u.apellido = data.apellido; u.legajo = data.legajo;
        u.email = data.email; if (u.rol === 'alumno') u.curso = data.curso;
      }
      App.toast('Usuario actualizado.', 'success');
      cerrarEditar();
      render();
    })
    .catch(err => App.toast(err.message, 'error'));
  });

  function renderPaginacionU(inicio, count) {
    const total = filtrado.length;
    const pages = Math.max(1, Math.ceil(total / PER_PAGE));

    App.qs('#pgInfo').textContent = total === 0 ? '' : `Mostrando ${inicio + 1}–${inicio + count} de ${total}`;

    const cont = App.qs('#pgBtns');
    cont.innerHTML = '';
    if (pages <= 1) return;

    const prev = document.createElement('button');
    prev.className = 'pg-btn'; prev.innerHTML = '<i class="fa-solid fa-chevron-left"></i>';
    prev.disabled = pagina <= 1;
    prev.onclick = () => { pagina--; render(); };
    cont.appendChild(prev);

    pageRangeU(pagina, pages).forEach(p => {
      if (p === '…') {
        const sp = document.createElement('span');
        sp.className = 'pg-btn'; sp.style.cursor = 'default'; sp.style.borderColor = 'transparent'; sp.textContent = '…';
        cont.appendChild(sp);
      } else {
        const btn = document.createElement('button');
        btn.className = 'pg-btn' + (p === pagina ? ' active' : '');
        btn.textContent = p;
        btn.onclick = () => { pagina = p; render(); };
        cont.appendChild(btn);
      }
    });

    const next = document.createElement('button');
    next.className = 'pg-btn'; next.innerHTML = '<i class="fa-solid fa-chevron-right"></i>';
    next.disabled = pagina >= pages;
    next.onclick = () => { pagina++; render(); };
    cont.appendChild(next);
  }

  // Siempre primera y última página, más una ventana de 3 (anterior/actual/siguiente)
  // alrededor de la página actual, corrida para no salirse del rango.
  function pageRangeU(cur, total) {
    if (total <= 1) return total === 1 ? [1] : [];

    let start = cur - 1, end = cur + 1;
    if (start < 1) { end += 1 - start; start = 1; }
    if (end > total) { start -= end - total; end = total; }
    start = Math.max(start, 1);
    end = Math.min(end, total);

    const shown = new Set([1, total]);
    for (let p = start; p <= end; p++) shown.add(p);
    const sorted = [...shown].sort((a, b) => a - b);

    const result = [];
    sorted.forEach((p, i) => {
      if (i > 0) {
        const gap = p - sorted[i - 1];
        if (gap === 2) result.push(sorted[i - 1] + 1); // se esconde 1 sola página: mostrala
        else if (gap > 2) result.push('…');
      }
      result.push(p);
    });
    return result;
  }

  App.qs('#filtroEstado').addEventListener('change', aplicarFiltros);
  App.qs('#filtroRol').addEventListener('change', aplicarFiltros);
  App.qs('#filtroBuscar').addEventListener('input', aplicarFiltros);

  aplicarFiltros();
</script>
</body>
</html>
