<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';
require_auth(['admin']);

$pdo    = getPDO();
$cursos = $pdo->query("SELECT nombre FROM cursos ORDER BY nombre")->fetchAll(PDO::FETCH_COLUMN);

$por_pagina = 15;
$pagina     = max(1, (int)($_GET['pagina'] ?? 1));
$buscar     = trim($_GET['buscar'] ?? '');
$offset     = ($pagina - 1) * $por_pagina;

if ($buscar) {
    $like = '%' . $buscar . '%';
    $cnt  = $pdo->prepare("SELECT COUNT(*) FROM usuarios WHERE (nombre LIKE ? OR apellido LIKE ? OR legajo LIKE ?) AND activo=1");
    $cnt->execute([$like, $like, $like]);
} else {
    $cnt = $pdo->query("SELECT COUNT(*) FROM usuarios WHERE activo=1");
}
$total_usuarios = (int)$cnt->fetchColumn();
$total_paginas  = max(1, (int)ceil($total_usuarios / $por_pagina));

if ($buscar) {
    $like = '%' . $buscar . '%';
    $stmt = $pdo->prepare(
        "SELECT id, legajo, nombre, apellido, rol, curso FROM usuarios
         WHERE (nombre LIKE ? OR apellido LIKE ? OR legajo LIKE ?) AND activo=1
         ORDER BY apellido, nombre LIMIT ? OFFSET ?"
    );
    $stmt->execute([$like, $like, $like, $por_pagina, $offset]);
} else {
    $stmt = $pdo->prepare(
        "SELECT id, legajo, nombre, apellido, rol, curso FROM usuarios
         WHERE activo=1 ORDER BY created_at DESC LIMIT ? OFFSET ?"
    );
    $stmt->execute([$por_pagina, $offset]);
}
$usuarios = $stmt->fetchAll();

$badge_rol = [
    'alumno'     => 'badge-accent',
    'profesor'   => 'badge-muted',
    'secretaria' => 'badge-warning',
    'admin'      => 'badge-danger',
];
$label_rol = [
    'alumno'     => 'Alumno',
    'profesor'   => 'Profesor',
    'secretaria' => 'Secretaria',
    'admin'      => 'Admin',
];

$partes    = explode(' ', $_SESSION['nombre']);
$iniciales = strtoupper(substr($partes[0],0,1).substr($partes[1]??'',0,1));

function url_pag_u(int $p): string {
    $q = $_GET; $q['pagina'] = $p;
    return '?' . http_build_query($q);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" /><meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Usuarios &middot; Admin</title>
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
          <form method="GET" style="display:flex;gap:8px">
            <input class="input" name="buscar" value="<?= htmlspecialchars($buscar) ?>" placeholder="Buscar por nombre o legajo..." style="min-width:240px" />
            <button type="submit" class="btn btn-ghost btn-sm"><i class="fa-solid fa-magnifying-glass"></i></button>
            <?php if ($buscar): ?><a href="usuarios.php" class="btn btn-ghost btn-sm"><i class="fa-solid fa-xmark"></i></a><?php endif; ?>
          </form>
        </div>
        <div class="table-scroll">
          <table class="data-table">
            <thead><tr><th>Nombre</th><th>Legajo</th><th>Curso</th><th>Rol</th></tr></thead>
            <tbody>
            <?php foreach ($usuarios as $u): ?>
              <tr>
                <td><?= htmlspecialchars($u['apellido'].', '.$u['nombre']) ?></td>
                <td><?= htmlspecialchars($u['legajo']) ?></td>
                <td><?= htmlspecialchars($u['curso'] ?? '&mdash;') ?></td>
                <td><span class="badge <?= $badge_rol[$u['rol']] ?? 'badge-muted' ?>"><?= $label_rol[$u['rol']] ?? htmlspecialchars($u['rol']) ?></span></td>
              </tr>
            <?php endforeach; ?>
            <?php if (empty($usuarios)): ?>
              <tr><td colspan="4" style="text-align:center;padding:32px;color:var(--c-text-faint)">No se encontraron usuarios.</td></tr>
            <?php endif; ?>
            </tbody>
          </table>
        </div>
        <?php if ($total_paginas > 1): ?>
        <div class="pagination">
          <span class="pg-info">Mostrando <?= $offset+1 ?>&ndash;<?= min($offset+$por_pagina,$total_usuarios) ?> de <?= $total_usuarios ?></span>
          <div class="pg-controls">
            <a href="<?= url_pag_u(max(1,$pagina-1)) ?>" class="pg-btn <?= $pagina<=1?'disabled':'' ?>"><i class="fa-solid fa-chevron-left"></i></a>
            <?php for ($i=1;$i<=$total_paginas;$i++): ?>
              <a href="<?= url_pag_u($i) ?>" class="pg-btn <?= $i===$pagina?'active':'' ?>"><?= $i ?></a>
            <?php endfor; ?>
            <a href="<?= url_pag_u(min($total_paginas,$pagina+1)) ?>" class="pg-btn <?= $pagina>=$total_paginas?'disabled':'' ?>"><i class="fa-solid fa-chevron-right"></i></a>
          </div>
        </div>
        <?php endif; ?>
      </div>

    </main>
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
</script>
</body>
</html>
