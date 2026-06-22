<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';
require_auth(['secretaria', 'admin']);

$pdo    = getPDO();
$cursos = $pdo->query("SELECT nombre FROM cursos ORDER BY nombre")->fetchAll(PDO::FETCH_COLUMN);

$partes    = explode(' ', $_SESSION['nombre']);
$iniciales = strtoupper(substr($partes[0],0,1).substr($partes[1]??'',0,1));
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" /><meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Alta de usuarios · Secretaría</title>
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
      <a href="materias.php"><i class="fa-solid fa-book"></i> Materias</a>
      <a href="inscripciones.php"><i class="fa-solid fa-user-plus"></i> Inscripciones</a>
      <a href="usuarios.php" class="active"><i class="fa-solid fa-users"></i> Alta de usuarios</a>
      <a href="reportes.php"><i class="fa-solid fa-chart-pie"></i> Reportes</a>
      <span class="nav-label">Cuenta</span>
      <a href="../logout.php"><i class="fa-solid fa-right-from-bracket"></i> Cerrar sesión</a>
    </nav>
    <div class="sidebar-user">
      <div class="avatar"><?= htmlspecialchars($iniciales) ?></div>
      <div class="meta"><div class="u-name"><?= htmlspecialchars($_SESSION['nombre']) ?></div><div class="u-role">Secretaría</div></div>
      <a href="../logout.php" class="logout"><i class="fa-solid fa-right-from-bracket"></i></a>
    </div>
  </aside>
  <div class="sidebar-backdrop" data-sidebar-backdrop></div>

  <div class="app-main">
    <header class="topbar">
      <button class="hamburger" data-sidebar-toggle aria-label="Menú"><i class="fa-solid fa-bars"></i></button>
      <div class="page-title">Alta de usuarios <small>Cargá alumnos, profesores o secretaría</small></div>
    </header>
    <main class="app-content">
      <div class="admin-grid" style="display:grid;grid-template-columns:1.4fr 1fr;gap:20px;align-items:start">

        <!-- Formulario de alta -->
        <div class="card" style="padding:24px">
          <h3 style="font-size:1.1rem;margin-bottom:4px">Nuevo usuario</h3>
          <p class="text-muted" style="font-size:0.88rem;margin-bottom:18px">Elegí el tipo y completá los datos.</p>

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
              <span class="tile"><i class="fa-solid fa-folder-open"></i> Secretaría</span>
            </label>
          </div>

          <form id="userForm">
            <div class="form-grid">
              <div class="field"><label>Nombre</label><input class="input" name="nombre" placeholder="Ej: Juan" required /></div>
              <div class="field"><label>Apellido</label><input class="input" name="apellido" placeholder="Ej: Pérez" required /></div>
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
          <p class="text-muted" style="font-size:0.88rem;margin-bottom:16px">Importá varios alumnos desde un Excel.</p>

          <label class="card" style="display:block;border:2px dashed var(--c-border);background:var(--c-bg);padding:24px;text-align:center;cursor:pointer;border-radius:14px">
            <i class="fa-solid fa-file-excel" style="font-size:1.8rem;color:var(--c-success)"></i>
            <div style="font-weight:700;margin-top:8px">Subir archivo Excel</div>
            <div class="text-muted" style="font-size:0.82rem">.xlsx o .csv — arrastrá o hacé clic</div>
            <input type="file" accept=".xlsx,.csv" hidden id="fileInput" />
          </label>
          <div id="fileName" class="text-muted mt-1" style="font-size:0.84rem"></div>

          <div class="card" style="background:var(--c-muted-soft);border:none;padding:14px;margin-top:18px;font-size:0.82rem;color:var(--c-text-soft)">
            <strong style="color:var(--c-text)">Formato esperado (5 columnas):</strong><br>
            <span style="display:inline-block;margin-top:6px;font-family:monospace">
              Nombre · Apellido · Legajo · Curso · Email
            </span><br><br>
            <strong style="color:var(--c-text)">Ejemplo:</strong><br>
            <span style="font-family:monospace">
              Juan · Pérez · 20001 · M1A · jperez@instituto.edu<br>
              María · López · 20002 · N1 · mlopez@instituto.edu
            </span><br><br>
            La columna Email es obligatoria. La contraseña inicial de cada alumno es su legajo.
          </div>
        </div>

      </div>
    </main>
  </div>
</div>
<script src="../assets/js/utils.js"></script>
<script>
  const labels = { alumno:'Agregar alumno', profesor:'Agregar profesor', secretaria:'Agregar secretaría' };

  function setTipo(tipo) {
    App.qsa('.campo-alumno').forEach(el => el.classList.toggle('hidden', tipo !== 'alumno'));
    App.qs('#submitLabel').textContent = labels[tipo];
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
    .then(function (res) {
      App.toast('Usuario creado. Contraseña inicial: legajo.', 'success');
      App.qs('#userForm').reset();
      App.qs('input[name="tipo"][value="alumno"]').checked = true;
      setTipo('alumno');
    })
    .catch(err => App.toast(err.message, 'error'));
  });

  // Importar Excel
  App.qs('#fileInput').addEventListener('change', function () {
    if (!this.files[0]) return;
    App.qs('#fileName').textContent = '📄 ' + this.files[0].name;
    const form = new FormData();
    form.append('archivo', this.files[0]);
    App.showLoader();
    fetch('../api/importar_alumnos.php', { method: 'POST', body: form })
      .then(r => r.json())
      .then(function (d) {
        App.hideLoader();
        let msg = 'Importados: ' + d.creados + ' alumnos.';
        if (d.errores && d.errores.length) {
          msg += ' — ' + d.errores.length + ' error(es):\n' + d.errores.slice(0, 5).join('\n');
          if (d.errores.length > 5) msg += '\n... y ' + (d.errores.length - 5) + ' más (ver consola)';
          console.table(d.errores);
        }
        App.toast(msg, d.creados > 0 ? 'success' : 'error');
      })
      .catch(function () { App.hideLoader(); App.toast('Error al importar.', 'error'); });
  });
</script>
</body>
</html>

