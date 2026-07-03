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
              <option value="alumno" <?= $rol_filtro === 'alumno' ? 'selected' : '' ?>>Alumnos</option>
              <option value="profesor" <?= $rol_filtro === 'profesor' ? 'selected' : '' ?>>Profesores</option>
              <option value="secretaria" <?= $rol_filtro === 'secretaria' ? 'selected' : '' ?>>Secretaría</option>
              <option value="admin" <?= $rol_filtro === 'admin' ? 'selected' : '' ?>>Admin</option>
            </select>
            <input class="input" type="search" id="buscarUsuario"
                   placeholder="Buscar por nombre o legajo…" style="min-width:200px"
                   value="<?= htmlspecialchars($buscar) ?>" />
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
              <tbody id="tablaUsuarios">
              <?php foreach ($usuarios as $u): ?>
                <?php
                  $nombre_completo = $u['nombre'] . ' ' . $u['apellido'];
                  $ini = strtoupper(substr($u['nombre'], 0, 1) . substr($u['apellido'], 0, 1));
                  $rol_badge = $badge_rol[$u['rol']] ?? 'badge-muted';
                  $rol_label = $label_rol[$u['rol']] ?? $u['rol'];
                ?>
                <tr>
                  <td>
                    <div class="cell-name" style="display: flex; align-items: center; gap: 10px;">
                      <span class="mini-avatar"><?= htmlspecialchars($ini) ?></span>
                      <div>
                        <div style="font-weight: 600; color: var(--c-text);"><?= htmlspecialchars($nombre_completo) ?></div>
                        <small class="text-muted" style="font-size: 0.78rem; font-weight: 500; display: block; margin-top: 2px;">Legajo: <?= htmlspecialchars($u['legajo']) ?></small>
                      </div>
                    </div>
                  </td>
                  <td><span class="badge <?= $rol_badge ?>"><?= $rol_label ?></span></td>
                  <td>
                    <span class="badge <?= $u['activo'] ? 'badge-success' : 'badge-danger' ?>">
                      <?= $u['activo'] ? 'Activo' : 'Inactivo' ?>
                    </span>
                  </td>
                  <td style="text-align: center;">
                    <label class="switch">
                      <input type="checkbox" class="toggle-usuario"
                             data-id="<?= $u['id'] ?>"
                             <?= $u['activo'] ? 'checked' : '' ?>>
                      <span class="slider"></span>
                    </label>
                  </td>
                </tr>
              <?php endforeach; ?>
              <?php if (empty($usuarios)): ?>
                <tr><td colspan="5" class="empty-row">No hay usuarios registrados.</td></tr>
              <?php endif; ?>
              </tbody>
            </table>
          </div>

          <!-- Paginación -->
          <?php
            $qs = '';
            if ($buscar !== '') {
                $qs .= '&buscar=' . urlencode($buscar);
            }
            if ($rol_filtro !== '') {
                $qs .= '&rol=' . urlencode($rol_filtro);
            }
            $mostrar_desde = $total_usuarios > 0 ? $offset + 1 : 0;
          ?>
          <div class="pagination">
            <span class="pg-info">
              <?php if ($buscar): ?>
                <?= $total_usuarios ?> resultado(s) para "<?= htmlspecialchars($buscar) ?>"
              <?php else: ?>
                Mostrando <?= $mostrar_desde ?>–<?= min($offset + $por_pagina, $total_usuarios) ?>
                de <?= $total_usuarios ?>
              <?php endif; ?>
            </span>
            <div class="pg-controls">
              <a href="?pagina=<?= max(1, $pagina - 1) . $qs ?>"
                 class="pg-btn <?= $pagina <= 1 ? 'disabled' : '' ?>">
                <i class="fa-solid fa-chevron-left"></i>
              </a>
              <?php
              $rango = get_page_range($pagina, $total_paginas);
              foreach ($rango as $p):
                  if ($p === '…'):
              ?>
                  <span class="pg-btn" style="cursor: default; border-color: transparent;">…</span>
              <?php else: ?>
                  <a href="?pagina=<?= $p . $qs ?>" class="pg-btn <?= $p === $pagina ? 'active' : '' ?>">
                    <?= $p ?>
                  </a>
              <?php
                  endif;
              endforeach;
              ?>
              <a href="?pagina=<?= min($total_paginas, $pagina + 1) . $qs ?>"
                 class="pg-btn <?= $pagina >= $total_paginas ? 'disabled' : '' ?>">
                <i class="fa-solid fa-chevron-right"></i>
              </a>
            </div>
          </div>
        </div>



      </div>
      </div>
    </main>
  </div>
</div>

<script src="../assets/js/utils.js"></script>
<script>
  // ── Toggle activar/desactivar usuario ──
  App.qsa('.toggle-usuario').forEach(function (sw) {
    sw.addEventListener('change', function () {
      const id     = this.dataset.id;
      const activo = this.checked ? 1 : 0;
      App.api('../api/toggle_usuario.php', {
        method: 'POST',
        body: JSON.stringify({ usuario_id: id, activo: activo }),
      })
      .then(function () {
        App.toast('Usuario ' + (activo ? 'activado' : 'desactivado') + '.', activo ? 'success' : 'info');
      })
      .catch(function (err) {
        App.toast(err.message, 'error');
        sw.checked = !sw.checked; // revierte el toggle si falla
      });
    });
  });



  // ── Filtro por Rol ──
  App.qs('#filtroRol').addEventListener('change', function () {
    const rol = this.value;
    const url = new URL(window.location.href);
    if (rol) {
      url.searchParams.set('rol', rol);
    } else {
      url.searchParams.delete('rol');
    }
    url.searchParams.set('pagina', '1');
    window.location.href = url.toString();
  });

  // ── Búsqueda server-side (con debounce) ──
  let searchTimer;
  App.qs('#buscarUsuario').addEventListener('input', function () {
    clearTimeout(searchTimer);
    const q = this.value;
    searchTimer = setTimeout(function () {
      const url = new URL(window.location.href);
      url.searchParams.set('buscar', q);
      url.searchParams.set('pagina', '1');
      window.location.href = url.toString();
    }, 400);
  });
</script>
</body>
</html>
