<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Administración · Asistencia QR</title>
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="../assets/vendor/fontawesome/css/all.min.css" />
  <link rel="stylesheet" href="../assets/css/main.css" />
  <link rel="stylesheet" href="../assets/css/dashboard.css" />
</head>
<body>
<div class="app-layout role-admin">

  <!-- ── Sidebar ── -->
  <aside class="sidebar">
    <div class="sidebar-brand">
      <img src="../assets/img/logo-dashboard.png" alt="Logo" />
      <div><div class="name">Asistencia QR</div><div class="sub">Administración</div></div>
    </div>
    <nav class="sidebar-nav">
      <span class="nav-label">Sistema</span>
      <a href="dashboard.php" class="active"><i class="fa-solid fa-gauge-high"></i> Resumen</a>
      <a href="usuarios.php"><i class="fa-solid fa-users"></i> Usuarios</a>
      <a href="materias.php"><i class="fa-solid fa-book"></i> Materias</a>
      <a href="aulas.php"><i class="fa-solid fa-door-open"></i> Aulas</a>
      <a href="configuracion.php"><i class="fa-solid fa-gear"></i> Configuración</a>
      <span class="nav-label">Cuenta</span>
      <a href="../logout.php"><i class="fa-solid fa-right-from-bracket"></i> Cerrar sesión</a>
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

  <!-- ── Contenido principal ── -->
  <div class="app-main">
    <header class="topbar">
      <button class="hamburger" data-sidebar-toggle aria-label="Menú"><i class="fa-solid fa-bars"></i></button>
      <div class="page-title">Panel de administración</div>
      <div class="topbar-right">
        <span class="topbar-date"><i class="fa-regular fa-calendar"></i> <?= $fecha_hoy ?></span>
      </div>
    </header>

    <main class="app-content">
      <div style="max-width: 1200px; margin: 0 auto;">

      <!-- ── Cards de resumen ── -->
      <div class="stat-grid">
        <div class="stat-card">
          <div class="stat-icon i-blue"><i class="fa-solid fa-user-graduate"></i></div>
          <div><div class="stat-value"><?= number_format($total_alumnos) ?></div><div class="stat-label">Alumnos</div></div>
        </div>
        <div class="stat-card">
          <div class="stat-icon i-navy"><i class="fa-solid fa-chalkboard-user"></i></div>
          <div><div class="stat-value"><?= number_format($total_profesores) ?></div><div class="stat-label">Profesores</div></div>
        </div>
        <div class="stat-card">
          <div class="stat-icon i-amber"><i class="fa-solid fa-book"></i></div>
          <div><div class="stat-value"><?= number_format($clases_hoy) ?></div><div class="stat-label">Clases hoy</div></div>
        </div>
        <div class="stat-card">
          <div class="stat-icon i-green"><i class="fa-solid fa-percent"></i></div>
          <div><div class="stat-value"><?= $asistencia_prom ?>%</div><div class="stat-label">Asistencia promedio</div></div>
        </div>
      </div>

      <div class="admin-grid">

        <!-- ── Tabla de usuarios ── -->
        <div class="card table-card">
          <div class="toolbar" style="padding:16px 16px 0;margin:0;gap:12px">
            <h3 style="font-size:1.05rem">Usuarios</h3>
            <div class="spacer"></div>
            <select class="select" id="filtroRol" style="height:38px;font-size:0.86rem;padding:0 12px;min-width:140px">
              <option value="">Todos los roles</option>
              <option value="alumno">Alumnos</option>
              <option value="profesor">Profesores</option>
              <option value="secretaria">Secretaría</option>
              <option value="admin">Admin</option>
            </select>
            <input class="input" type="search" id="buscarUsuario"
                   placeholder="Buscar por nombre o legajo…" style="min-width:200px" />
          </div>
          <div class="table-scroll">
            <table class="data-table">
              <thead>
                <tr>
                  <th>Usuario</th>
                  <th style="width: 160px;">Rol</th>
                  <th style="width: 130px;">Estado</th>
                  <th style="width: 100px; text-align: center;">Activo</th>
                </tr>
              </thead>
              <tbody id="tablaUsuarios"></tbody>
            </table>
          </div>

          <!-- Paginación -->
          <div class="pagination">
            <span class="pg-info" id="pgInfo"></span>
            <div class="pg-controls" id="pgBtns"></div>
          </div>
        </div>



      </div>
      </div>
    </main>
  </div>
</div>

<script src="../assets/js/utils.js"></script>
<script>
  // ── Listado de usuarios: filtro y paginación en el navegador ────────────
  const ALL_USERS = <?= json_encode(array_values($usuarios)) ?>;
  const PER_PAGE_U = 5;
  const BADGE_ROL = { alumno:'badge-accent', profesor:'badge-muted', secretaria:'badge-warning', admin:'badge-danger' };
  const LABEL_ROL = { alumno:'Alumno', profesor:'Profesor', secretaria:'Secretaría', admin:'Admin' };

  let filtradoU = [];
  let paginaU   = 1;

  function escD(s) {
    return String(s ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
  }

  function aplicarFiltrosU() {
    const rol = App.qs('#filtroRol').value;
    const q   = App.qs('#buscarUsuario').value.toLowerCase().trim();

    filtradoU = ALL_USERS.filter(u => {
      if (rol && u.rol !== rol) return false;
      if (q && !(u.nombre.toLowerCase().includes(q) || u.apellido.toLowerCase().includes(q) || u.legajo.toLowerCase().includes(q))) return false;
      return true;
    });
    paginaU = 1;
    renderU();
  }

  function rowHtmlD(u) {
    const ini = (u.nombre.charAt(0) + u.apellido.charAt(0)).toUpperCase();
    return `
      <tr>
        <td>
          <div class="cell-name" style="display: flex; align-items: center; gap: 10px;">
            <span class="mini-avatar">${escD(ini)}</span>
            <div>
              <div style="font-weight: 600; color: var(--c-text);">${escD(u.nombre)} ${escD(u.apellido)}</div>
              <small class="text-muted" style="font-size: 0.78rem; font-weight: 500; display: block; margin-top: 2px;">Legajo: ${escD(u.legajo)}</small>
            </div>
          </div>
        </td>
        <td><span class="badge ${BADGE_ROL[u.rol] || 'badge-muted'}">${LABEL_ROL[u.rol] || escD(u.rol)}</span></td>
        <td><span class="badge ${u.activo == 1 ? 'badge-success' : 'badge-danger'}">${u.activo == 1 ? 'Activo' : 'Inactivo'}</span></td>
        <td style="text-align: center;">
          <label class="switch">
            <input type="checkbox" class="toggle-usuario" data-id="${u.id}" ${u.activo == 1 ? 'checked' : ''}>
            <span class="slider"></span>
          </label>
        </td>
      </tr>`;
  }

  function renderU() {
    const tbody  = App.qs('#tablaUsuarios');
    const inicio = (paginaU - 1) * PER_PAGE_U;
    const items  = filtradoU.slice(inicio, inicio + PER_PAGE_U);

    tbody.innerHTML = items.length
      ? items.map(rowHtmlD).join('')
      : '<tr><td colspan="4" class="empty-row">No hay usuarios registrados.</td></tr>';

    renderPaginacionU(inicio, items.length);
  }

  function renderPaginacionU(inicio, count) {
    const total = filtradoU.length;
    const pages = Math.max(1, Math.ceil(total / PER_PAGE_U));

    App.qs('#pgInfo').textContent = total === 0 ? '' : `Mostrando ${inicio + 1}–${inicio + count} de ${total}`;

    const cont = App.qs('#pgBtns');
    cont.innerHTML = '';
    if (pages <= 1) return;

    const prev = document.createElement('button');
    prev.className = 'pg-btn'; prev.innerHTML = '<i class="fa-solid fa-chevron-left"></i>';
    prev.disabled = paginaU <= 1;
    prev.onclick = () => { paginaU--; renderU(); };
    cont.appendChild(prev);

    pageRangeD(paginaU, pages).forEach(p => {
      if (p === '…') {
        const sp = document.createElement('span');
        sp.className = 'pg-btn'; sp.style.cursor = 'default'; sp.style.borderColor = 'transparent'; sp.textContent = '…';
        cont.appendChild(sp);
      } else {
        const btn = document.createElement('button');
        btn.className = 'pg-btn' + (p === paginaU ? ' active' : '');
        btn.textContent = p;
        btn.onclick = () => { paginaU = p; renderU(); };
        cont.appendChild(btn);
      }
    });

    const next = document.createElement('button');
    next.className = 'pg-btn'; next.innerHTML = '<i class="fa-solid fa-chevron-right"></i>';
    next.disabled = paginaU >= pages;
    next.onclick = () => { paginaU++; renderU(); };
    cont.appendChild(next);
  }

  // Siempre primera y última página, más una ventana de 3 (anterior/actual/siguiente)
  // alrededor de la página actual, corrida para no salirse del rango.
  function pageRangeD(cur, total) {
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

  App.qs('#filtroRol').addEventListener('change', aplicarFiltrosU);
  App.qs('#buscarUsuario').addEventListener('input', aplicarFiltrosU);

  // ── Toggle activar/desactivar usuario (delegado: las filas se re-crean al paginar/filtrar) ──
  App.qs('#tablaUsuarios').addEventListener('change', function (e) {
    const sw = e.target.closest('.toggle-usuario');
    if (!sw) return;

    const id     = sw.dataset.id;
    const activo = sw.checked ? 1 : 0;
    App.api('../api/toggle_usuario.php', {
      method: 'POST',
      body: JSON.stringify({ usuario_id: id, activo: activo }),
    })
    .then(function () {
      const u = ALL_USERS.find(u => String(u.id) === String(id));
      if (u) u.activo = activo;
      App.toast('Usuario ' + (activo ? 'activado' : 'desactivado') + '.', activo ? 'success' : 'info');
      renderU();
    })
    .catch(function (err) {
      App.toast(err.message, 'error');
      sw.checked = !sw.checked; // revierte el toggle si falla
    });
  });

  aplicarFiltrosU();
</script>
</body>
</html>
