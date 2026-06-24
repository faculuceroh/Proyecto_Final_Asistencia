<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Usuarios · Administración</title>
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
      <div><div class="name">Asistencia QR</div><div class="sub">Administración</div></div>
    </div>
    <nav class="sidebar-nav">
      <span class="nav-label">Sistema</span>
      <a href="dashboard.php"><i class="fa-solid fa-gauge-high"></i> Resumen</a>
      <a href="usuarios.php" class="active"><i class="fa-solid fa-users"></i> Usuarios</a>
      <a href="materias.php"><i class="fa-solid fa-book"></i> Materias</a>
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

  <div class="app-main">
    <header class="topbar">
      <button class="hamburger" data-sidebar-toggle aria-label="Menú"><i class="fa-solid fa-bars"></i></button>
      <div class="page-title">Usuarios <small>Gestión de cuentas del sistema</small></div>
    </header>

    <main class="app-content">
      <form method="GET" action="usuarios.php" id="filtrosForm">
        <div class="toolbar">
          <div class="filters">
            <input class="input" type="search" name="q" id="search"
                   placeholder="Buscar por nombre o legajo…"
                   value="<?= htmlspecialchars($q) ?>"
                   style="min-width:220px" />
            <select class="select" name="rol" id="fRol">
              <option value="">Todos los roles</option>
              <?php foreach (['alumno'=>'Alumno','profesor'=>'Profesor','secretaria'=>'Secretaría','admin'=>'Admin'] as $v => $l): ?>
                <option value="<?= $v ?>" <?= $rol === $v ? 'selected' : '' ?>><?= $l ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="spacer"></div>
          <a href="dashboard.php" class="btn btn-primary btn-sm">
            <i class="fa-solid fa-user-plus"></i> Nuevo usuario
          </a>
        </div>
      </form>

      <div class="card table-card">
        <div class="table-scroll">
          <table class="data-table" id="usersTable">
            <thead>
              <tr><th>Usuario</th><th>Legajo</th><th>Rol</th><th>Estado</th><th>Activo</th></tr>
            </thead>
            <tbody>
            <?php foreach ($usuarios as $u): ?>
              <?php
                $ini      = strtoupper(substr($u['nombre'],0,1) . substr($u['apellido'],0,1));
                $rb       = $badge_rol[$u['rol']] ?? 'badge-muted';
                $rl       = $label_rol[$u['rol']] ?? $u['rol'];
              ?>
              <tr data-rol="<?= $u['rol'] ?>">
                <td>
                  <div class="cell-name">
                    <span class="mini-avatar"><?= htmlspecialchars($ini) ?></span>
                    <?= htmlspecialchars($u['nombre'] . ' ' . $u['apellido']) ?>
                  </div>
                </td>
                <td><?= htmlspecialchars($u['legajo']) ?></td>
                <td><span class="badge <?= $rb ?>"><?= $rl ?></span></td>
                <td>
                  <span class="badge <?= $u['activo'] ? 'badge-success' : 'badge-danger' ?>">
                    <?= $u['activo'] ? 'Activo' : 'Inactivo' ?>
                  </span>
                </td>
                <td>
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
              <tr><td colspan="5" style="text-align:center;padding:32px;color:var(--c-text-faint)">
                No se encontraron usuarios.
              </td></tr>
            <?php endif; ?>
            </tbody>
          </table>
        </div>

        <div class="pagination">
          <span class="pg-info">
            Mostrando <?= $offset + 1 ?>–<?= min($offset + $por_pag, $total) ?> de <?= $total ?>
          </span>
          <div class="pg-controls">
            <a href="<?= url_pag(max(1, $pagina - 1)) ?>"
               class="pg-btn <?= $pagina <= 1 ? 'disabled' : '' ?>">
              <i class="fa-solid fa-chevron-left"></i>
            </a>
            <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
              <a href="<?= url_pag($i) ?>" class="pg-btn <?= $i === $pagina ? 'active' : '' ?>">
                <?= $i ?>
              </a>
            <?php endfor; ?>
            <a href="<?= url_pag(min($total_paginas, $pagina + 1)) ?>"
               class="pg-btn <?= $pagina >= $total_paginas ? 'disabled' : '' ?>">
                <i class="fa-solid fa-chevron-right"></i>
              </a>
          </div>
        </div>
      </div>
    </main>
  </div>
</div>

<script src="../assets/js/utils.js"></script>
<script>
  // Filtros: envía el form automáticamente al cambiar rol o buscar
  App.qs('#fRol').addEventListener('change', () => App.qs('#filtrosForm').submit());
  let searchTimer;
  App.qs('#search').addEventListener('input', function () {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(() => App.qs('#filtrosForm').submit(), 500);
  });

  // Toggle activar/desactivar
  App.qsa('.toggle-usuario').forEach(function (sw) {
    sw.addEventListener('change', function () {
      const id     = this.dataset.id;
      const activo = this.checked ? 1 : 0;
      const fila   = this.closest('tr');
      App.api('../api/toggle_usuario.php', {
        method: 'POST',
        body: JSON.stringify({ usuario_id: id, activo }),
      })
      .then(function () {
        const badge = fila.querySelector('.badge.badge-success, .badge.badge-danger');
        if (badge) {
          badge.className = 'badge ' + (activo ? 'badge-success' : 'badge-danger');
          badge.textContent = activo ? 'Activo' : 'Inactivo';
        }
        App.toast('Usuario ' + (activo ? 'activado' : 'desactivado') + '.', activo ? 'success' : 'info');
      })
      .catch(function (err) {
        App.toast(err.message, 'error');
        sw.checked = !sw.checked;
      });
    });
  });
</script>
</body>
</html>
