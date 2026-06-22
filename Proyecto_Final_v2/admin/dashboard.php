<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';
require_auth(['admin']);

$pdo = getPDO();

$cursos = $pdo->query("SELECT nombre FROM cursos ORDER BY nombre")->fetchAll(PDO::FETCH_COLUMN);

// ── Stats ────────────────────────────────────────────────────
$total_alumnos    = $pdo->query("SELECT COUNT(*) FROM usuarios WHERE rol='alumno'  AND activo=1")->fetchColumn();
$total_profesores = $pdo->query("SELECT COUNT(*) FROM usuarios WHERE rol='profesor' AND activo=1")->fetchColumn();
$clases_hoy       = $pdo->query("SELECT COUNT(*) FROM clases WHERE fecha = CURDATE()")->fetchColumn();
$asistencia_prom  = $pdo->query(
    "SELECT ROUND(SUM(estado IN ('presente','tardanza')) / NULLIF(COUNT(*),0) * 100, 1) FROM asistencias"
)->fetchColumn() ?? 0;

// ── Tabla de usuarios (paginada + búsqueda) ──────────────────
$por_pagina = 10;
$pagina     = max(1, (int) ($_GET['pagina'] ?? 1));
$buscar     = trim($_GET['buscar'] ?? '');
$offset     = ($pagina - 1) * $por_pagina;

if ($buscar) {
    $like = '%' . $buscar . '%';
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM usuarios WHERE nombre LIKE ? OR apellido LIKE ? OR legajo LIKE ?");
    $stmt->execute([$like, $like, $like]);
} else {
    $stmt = $pdo->query("SELECT COUNT(*) FROM usuarios");
}
$total_usuarios = (int) $stmt->fetchColumn();
$total_paginas  = (int) ceil($total_usuarios / $por_pagina);

if ($buscar) {
    $like = '%' . $buscar . '%';
    $stmt = $pdo->prepare(
        "SELECT id, legajo, nombre, apellido, rol, activo
         FROM usuarios
         WHERE nombre LIKE ? OR apellido LIKE ? OR legajo LIKE ?
         ORDER BY apellido, nombre
         LIMIT ? OFFSET ?"
    );
    $stmt->execute([$like, $like, $like, $por_pagina, $offset]);
} else {
    $stmt = $pdo->prepare(
        "SELECT id, legajo, nombre, apellido, rol, activo
         FROM usuarios ORDER BY created_at DESC
         LIMIT ? OFFSET ?"
    );
    $stmt->execute([$por_pagina, $offset]);
}
$usuarios = $stmt->fetchAll();

// ── Helpers de presentación ───────────────────────────────────
$badge_rol = [
    'alumno'     => 'badge-accent',
    'profesor'   => 'badge-muted',
    'secretaria' => 'badge-warning',
    'admin'      => 'badge-danger',
];
$label_rol = [
    'alumno'     => 'Alumno',
    'profesor'   => 'Profesor',
    'secretaria' => 'Secretaría',
    'admin'      => 'Admin',
];

// Fecha en español
$dias   = ['domingo','lunes','martes','miércoles','jueves','viernes','sábado'];
$fecha_hoy = ucfirst($dias[date('w')]) . ' ' . date('d/m/Y');

// Iniciales del usuario logueado
$partes    = explode(' ', $_SESSION['nombre']);
$iniciales = strtoupper(substr($partes[0], 0, 1) . substr($partes[1] ?? '', 0, 1));
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Administración · Asistencia QR</title>
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
  <link rel="stylesheet" href="../assets/css/main.css" />
  <link rel="stylesheet" href="../assets/css/dashboard.css" />
</head>
<body>
<div class="app-layout">

  <!-- ── Sidebar ── -->
  <aside class="sidebar">
    <div class="sidebar-brand">
      <img src="../assets/img/logo.png" alt="Logo" />
      <div><div class="name">Asistencia QR</div><div class="sub">Administración</div></div>
    </div>
    <nav class="sidebar-nav">
      <span class="nav-label">Sistema</span>
      <a href="dashboard.php" class="active"><i class="fa-solid fa-gauge-high"></i> Resumen</a>
      <a href="usuarios.php"><i class="fa-solid fa-users"></i> Usuarios</a>
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

      <div style="display:grid;grid-template-columns:1.6fr 1fr;gap:20px;align-items:start" class="admin-grid">

        <!-- ── Tabla de usuarios ── -->
        <div class="card table-card">
          <div class="toolbar" style="padding:16px 16px 0;margin:0">
            <h3 style="font-size:1.05rem">Usuarios</h3>
            <div class="spacer"></div>
            <input class="input" type="search" id="buscarUsuario"
                   placeholder="Buscar por nombre o legajo…" style="min-width:200px"
                   value="<?= htmlspecialchars($buscar) ?>" />
          </div>
          <div class="table-scroll">
            <table class="data-table">
              <thead>
                <tr><th>Usuario</th><th>Legajo</th><th>Rol</th><th>Estado</th><th>Activo</th></tr>
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
                    <div class="cell-name">
                      <span class="mini-avatar"><?= htmlspecialchars($ini) ?></span>
                      <?= htmlspecialchars($nombre_completo) ?>
                    </div>
                  </td>
                  <td><?= htmlspecialchars($u['legajo']) ?></td>
                  <td><span class="badge <?= $rol_badge ?>"><?= $rol_label ?></span></td>
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
                <tr><td colspan="5" class="empty-row">No hay usuarios registrados.</td></tr>
              <?php endif; ?>
              </tbody>
            </table>
          </div>

          <!-- Paginación -->
          <?php
            $qs = $buscar ? '&buscar=' . urlencode($buscar) : '';
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
              <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
                <a href="?pagina=<?= $i . $qs ?>" class="pg-btn <?= $i === $pagina ? 'active' : '' ?>">
                  <?= $i ?>
                </a>
              <?php endfor; ?>
              <a href="?pagina=<?= min($total_paginas, $pagina + 1) . $qs ?>"
                 class="pg-btn <?= $pagina >= $total_paginas ? 'disabled' : '' ?>">
                <i class="fa-solid fa-chevron-right"></i>
              </a>
            </div>
          </div>
        </div>

        <!-- ── Cargar alumnos ── -->
        <div class="card" style="padding:22px">
          <h3 style="font-size:1.05rem;margin-bottom:4px">Cargar alumnos</h3>
          <p class="text-muted" style="font-size:0.86rem;margin-bottom:18px">
            Importá un Excel o cargá un alumno manualmente.
          </p>

          <label class="card" id="dropZone"
                 style="display:block;border:2px dashed var(--c-border);background:var(--c-bg);
                        padding:24px;text-align:center;cursor:pointer;border-radius:14px">
            <i class="fa-solid fa-file-excel" style="font-size:1.8rem;color:var(--c-success)"></i>
            <div style="font-weight:700;margin-top:8px">Subir archivo Excel</div>
            <div class="text-muted" style="font-size:0.82rem">.xlsx o .csv — arrastrá o hacé clic</div>
            <input type="file" accept=".xlsx,.csv" hidden id="fileInput" />
          </label>
          <div id="fileName" class="text-muted mt-1" style="font-size:0.84rem"></div>

          <div style="display:flex;align-items:center;gap:10px;margin:18px 0;
                      color:var(--c-text-faint);font-size:0.8rem">
            <span style="flex:1;height:1px;background:var(--c-border)"></span>
            o cargá manual
            <span style="flex:1;height:1px;background:var(--c-border)"></span>
          </div>

          <form id="manualForm">
            <div class="field">
              <label>Nombre</label>
              <input class="input" name="nombre" placeholder="Ej: Juan" required />
            </div>
            <div class="field">
              <label>Apellido</label>
              <input class="input" name="apellido" placeholder="Ej: Pérez" required />
            </div>
            <div class="field">
              <label>Legajo</label>
              <input class="input" name="legajo" placeholder="Ej: 20460" inputmode="numeric" required />
            </div>
            <div class="field">
              <label>Curso</label>
              <select class="select" name="curso">
                <?php foreach ($cursos as $c): ?>
                  <option><?= htmlspecialchars($c) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <button type="submit" class="btn btn-primary btn-block">
              <i class="fa-solid fa-user-plus"></i> Agregar alumno
            </button>
          </form>
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

  // ── Alta manual de alumno ──
  App.qs('#manualForm').addEventListener('submit', function (e) {
    e.preventDefault();
    const data = Object.fromEntries(new FormData(this));
    App.api('../api/crear_usuario.php', {
      method: 'POST',
      loader: true,
      body: JSON.stringify({ tipo: 'alumno', ...data }),
    })
    .then(function () {
      App.toast('Alumno agregado correctamente.', 'success');
      App.qs('#manualForm').reset();
      setTimeout(() => location.reload(), 1000);
    })
    .catch(function (err) {
      App.toast(err.message, 'error');
    });
  });

  // ── Importar Excel ──
  App.qs('#fileInput').addEventListener('change', function () {
    if (!this.files[0]) return;
    App.qs('#fileName').textContent = '📄 ' + this.files[0].name;
    const form = new FormData();
    form.append('archivo', this.files[0]);
    fetch('../api/importar_alumnos.php', { method: 'POST', body: form })
      .then(r => r.json())
      .then(function (d) {
        App.toast('Importados: ' + d.creados + ' alumnos.', 'success');
        setTimeout(() => location.reload(), 1200);
      })
      .catch(function () {
        App.toast('Error al importar el archivo.', 'error');
      });
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
