<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';
require_auth(['secretaria', 'admin']);

$pdo = getPDO();

$materias = $pdo->query(
    "SELECT id, CONCAT(nombre, ' · ', curso) AS label FROM materias WHERE activo=1 ORDER BY nombre"
)->fetchAll();

// Materia seleccionada (filtro GET)
$sel_materia = (int)($_GET['materia_id'] ?? 0) ?: ($materias[0]['id'] ?? 0);

// Alumnos inscriptos en la materia seleccionada
$inscritos = [];
$mat_info  = null;
if ($sel_materia) {
    $stmt = $pdo->prepare(
        "SELECT m.nombre, m.curso FROM materias m WHERE m.id = ? LIMIT 1"
    );
    $stmt->execute([$sel_materia]);
    $mat_info = $stmt->fetch();

    $stmt = $pdo->prepare(
        "SELECT u.legajo, u.nombre, u.apellido, u.curso AS alumno_curso
         FROM inscripciones i
         JOIN usuarios u ON u.id = i.alumno_id
         WHERE i.materia_id = ?
         ORDER BY u.apellido, u.nombre"
    );
    $stmt->execute([$sel_materia]);
    $inscritos = $stmt->fetchAll();
}

// Todos los alumnos para el buscador de alta manual
$todos_alumnos = $pdo->query(
    "SELECT id, legajo, nombre, apellido, curso
     FROM usuarios WHERE rol='alumno' AND activo=1 ORDER BY apellido, nombre"
)->fetchAll();

$partes    = explode(' ', $_SESSION['nombre']);
$iniciales = strtoupper(substr($partes[0],0,1).substr($partes[1]??'',0,1));
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" /><meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Inscripciones · Secretaría</title>
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
      <a href="inscripciones.php" class="active"><i class="fa-solid fa-user-plus"></i> Inscripciones</a>
      <a href="usuarios.php"><i class="fa-solid fa-users"></i> Alta de usuarios</a>
      <a href="reportes.php"><i class="fa-solid fa-chart-pie"></i> Reportes</a>
      <span class="nav-label">Cuenta</span>
      <a href="perfil.php"><i class="fa-solid fa-user"></i> Mi perfil</a>
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
      <div class="page-title">Inscripciones <small>Asignale alumnos a cada materia</small></div>
    </header>
    <main class="app-content">

      <!-- Selector de materia -->
      <div class="card" style="padding:16px 20px;margin-bottom:18px">
        <form method="GET" action="inscripciones.php" style="display:flex;gap:10px;align-items:center;flex-wrap:wrap">
          <label style="font-weight:600;white-space:nowrap">Materia:</label>
          <select class="select" name="materia_id" style="flex:1;min-width:220px"
                  onchange="this.form.submit()">
            <?php foreach ($materias as $m): ?>
              <option value="<?= $m['id'] ?>" <?= $sel_materia == $m['id'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($m['label']) ?>
              </option>
            <?php endforeach; ?>
          </select>
          <span class="badge badge-accent"><?= count($inscritos) ?> inscriptos</span>
        </form>
      </div>

      <div style="display:grid;grid-template-columns:1fr 1.6fr;gap:20px;align-items:start">

        <!-- Panel izquierdo: alta manual + carga masiva -->
        <div style="display:flex;flex-direction:column;gap:16px">

          <!-- Alta manual -->
          <div class="card" style="padding:22px">
            <h3 style="font-size:1rem;margin-bottom:14px">Inscribir alumno</h3>
            <form id="manualForm" autocomplete="off">
              <div class="field" style="position:relative">
                <label>Buscar por nombre o legajo</label>
                <div class="input-group">
                  <i class="input-icon fa-solid fa-magnifying-glass"></i>
                  <input class="input" type="text" id="alumnoSearch"
                         placeholder="Ej: García o 20451" />
                </div>
                <input type="hidden" id="alumnoLegajo" />
                <!-- Resultados del buscador -->
                <div id="searchResults" style="
                  display:none;position:absolute;top:100%;left:0;right:0;z-index:50;
                  background:var(--c-surface);border:1px solid var(--c-border);
                  border-radius:10px;box-shadow:0 8px 24px rgba(0,0,0,.1);
                  max-height:220px;overflow-y:auto;margin-top:4px"></div>
              </div>
              <!-- Alumno seleccionado -->
              <div id="alumnoSeleccionado" style="display:none;
                background:var(--c-muted-soft);border-radius:8px;padding:10px 14px;
                font-size:0.88rem;margin-bottom:12px;display:none;align-items:center;gap:10px">
                <span class="mini-avatar" id="selIni"></span>
                <div><div id="selNombre" style="font-weight:600"></div>
                <div id="selLegajo" style="color:var(--c-text-soft);font-size:0.8rem"></div></div>
                <button type="button" id="clearAlumno" style="margin-left:auto;background:none;
                  border:none;cursor:pointer;color:var(--c-text-faint);font-size:1rem">
                  <i class="fa-solid fa-xmark"></i>
                </button>
              </div>
              <button type="submit" class="btn btn-primary btn-block">
                <i class="fa-solid fa-user-plus"></i> Inscribir
              </button>
            </form>
          </div>

          <!-- Carga masiva por archivo -->
          <div class="card" style="padding:22px">
            <h3 style="font-size:1rem;margin-bottom:6px">Carga masiva</h3>
            <p class="text-muted" style="font-size:0.84rem;margin-bottom:14px">
              Subí un CSV o Excel con una columna de legajos. Los alumnos deben existir en el sistema.
            </p>
            <label style="display:block;border:2px dashed var(--c-border);background:var(--c-bg);
                          padding:20px;text-align:center;cursor:pointer;border-radius:12px">
              <i class="fa-solid fa-file-excel" style="font-size:1.6rem;color:var(--c-success)"></i>
              <div style="font-weight:700;margin-top:8px;font-size:0.9rem">Subir CSV / Excel</div>
              <div class="text-muted" style="font-size:0.8rem">Una columna: Legajo</div>
              <input type="file" accept=".csv,.xlsx" hidden id="archivoInput" />
            </label>
            <div id="archivoNombre" class="text-muted mt-1" style="font-size:0.83rem"></div>
            <div class="card" style="background:var(--c-muted-soft);border:none;padding:12px;
                                     margin-top:14px;font-size:0.8rem;color:var(--c-text-soft)">
              <strong style="color:var(--c-text)">Ejemplo de CSV:</strong><br>
              20001<br>20002<br>20003
            </div>
          </div>
        </div>

        <!-- Tabla de inscriptos -->
        <div class="card table-card">
          <div class="toolbar" style="padding:14px 16px 0;margin:0">
            <h3 style="font-size:1rem">
              <?= $mat_info ? htmlspecialchars($mat_info['nombre'].' · '.$mat_info['curso']) : 'Inscriptos' ?>
            </h3>
            <div class="spacer"></div>
            <span class="badge badge-accent" id="totalBadge"><?= count($inscritos) ?> alumnos</span>
          </div>
          <div class="table-scroll">
            <table class="data-table" id="inscriptosTable">
              <thead><tr><th>Alumno</th><th>Legajo</th><th>Curso</th><th></th></tr></thead>
              <tbody>
              <?php foreach ($inscritos as $a): ?>
                <tr data-legajo="<?= $a['legajo'] ?>">
                  <td>
                    <div class="cell-name">
                      <span class="mini-avatar"><?= strtoupper(substr($a['nombre'],0,1).substr($a['apellido'],0,1)) ?></span>
                      <?= htmlspecialchars($a['nombre'].' '.$a['apellido']) ?>
                    </div>
                  </td>
                  <td><?= $a['legajo'] ?></td>
                  <td><?= htmlspecialchars($a['alumno_curso'] ?? '—') ?></td>
                  <td>
                    <button class="btn btn-danger btn-sm btn-desinscribir"
                            data-legajo="<?= $a['legajo'] ?>">
                      <i class="fa-solid fa-xmark"></i>
                    </button>
                  </td>
                </tr>
              <?php endforeach; ?>
              <?php if(empty($inscritos)): ?>
                <tr id="emptyRow"><td colspan="4" style="text-align:center;padding:28px;color:var(--c-text-faint)">
                  Sin alumnos inscriptos en esta materia.
                </td></tr>
              <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>

      </div>
    </main>
  </div>
</div>
<script src="../assets/js/utils.js"></script>
<script>
  const MATERIA_ID = <?= $sel_materia ?>;

  // Datos de alumnos inyectados desde PHP
  const ALUMNOS = <?= json_encode(array_values($todos_alumnos)) ?>;

  // ── Buscador de alumnos ───────────────────────────────────────
  const searchInput  = App.qs('#alumnoSearch');
  const searchBox    = App.qs('#searchResults');
  const legajoHidden = App.qs('#alumnoLegajo');
  const selCard      = App.qs('#alumnoSeleccionado');
  const selIni       = App.qs('#selIni');
  const selNombre    = App.qs('#selNombre');
  const selLegajo    = App.qs('#selLegajo');

  let alumnoSel = null;

  searchInput.addEventListener('input', function () {
    const q = this.value.trim().toLowerCase();
    if (q.length < 1) { searchBox.style.display = 'none'; return; }

    const matches = ALUMNOS.filter(a =>
      a.apellido.toLowerCase().includes(q) ||
      a.nombre.toLowerCase().includes(q) ||
      a.legajo.toLowerCase().includes(q)
    ).slice(0, 10);

    if (!matches.length) {
      searchBox.innerHTML = '<div style="padding:12px 16px;color:var(--c-text-faint);font-size:0.88rem">Sin resultados</div>';
    } else {
      searchBox.innerHTML = matches.map(a => `
        <div class="search-result-item" data-legajo="${a.legajo}"
             data-nombre="${a.nombre}" data-apellido="${a.apellido}" data-curso="${a.curso||'—'}"
             style="padding:10px 16px;cursor:pointer;display:flex;align-items:center;gap:10px;
                    font-size:0.88rem;border-bottom:1px solid var(--c-border)">
          <span class="mini-avatar">${a.apellido[0]?.toUpperCase()||''}${a.nombre[0]?.toUpperCase()||''}</span>
          <div>
            <div style="font-weight:600">${a.apellido}, ${a.nombre}</div>
            <div style="color:var(--c-text-soft)">Legajo ${a.legajo} · ${a.curso||'—'}</div>
          </div>
        </div>`).join('');
    }
    searchBox.style.display = 'block';
  });

  searchBox.addEventListener('click', function (e) {
    const item = e.target.closest('.search-result-item');
    if (!item) return;
    alumnoSel = item.dataset;
    legajoHidden.value = item.dataset.legajo;
    selIni.textContent = item.dataset.apellido[0].toUpperCase() + item.dataset.nombre[0].toUpperCase();
    selNombre.textContent = item.dataset.apellido + ', ' + item.dataset.nombre;
    selLegajo.textContent = 'Legajo ' + item.dataset.legajo + ' · ' + item.dataset.curso;
    selCard.style.display = 'flex';
    searchInput.value = '';
    searchBox.style.display = 'none';
  });

  App.qs('#clearAlumno').addEventListener('click', function () {
    alumnoSel = null;
    legajoHidden.value = '';
    selCard.style.display = 'none';
    searchInput.value = '';
  });

  document.addEventListener('click', function (e) {
    if (!e.target.closest('#manualForm')) searchBox.style.display = 'none';
  });

  // Hover en resultados
  document.addEventListener('mouseover', function (e) {
    const item = e.target.closest('.search-result-item');
    if (item) item.style.background = 'var(--c-muted-soft)';
  });
  document.addEventListener('mouseout', function (e) {
    const item = e.target.closest('.search-result-item');
    if (item) item.style.background = '';
  });

  // ── Alta manual ───────────────────────────────────────────────
  App.qs('#manualForm').addEventListener('submit', function (e) {
    e.preventDefault();
    const legajo = legajoHidden.value;
    if (!legajo) { App.toast('Buscá y seleccioná un alumno primero.', 'error'); return; }

    App.api('../api/inscribir_alumnos.php', {
      method: 'POST', loader: true,
      body: JSON.stringify({ materia_id: MATERIA_ID, legajos: [legajo] }),
    })
    .then(function (res) {
      if (res.inscritos > 0) {
        const ini = alumnoSel.apellido[0].toUpperCase() + alumnoSel.nombre[0].toUpperCase();
        agregarFila(legajo, alumnoSel.apellido + ', ' + alumnoSel.nombre, ini, alumnoSel.curso);
        App.toast('Alumno inscripto.', 'success');
      } else if (res.ya_inscritos > 0) {
        App.toast('El alumno ya estaba inscripto.', 'error');
      } else {
        App.toast('No se encontró el alumno.', 'error');
      }
      App.qs('#clearAlumno').click();
    })
    .catch(err => App.toast(err.message, 'error'));
  });

  // ── Carga masiva ──────────────────────────────────────────────
  App.qs('#archivoInput').addEventListener('change', function () {
    if (!this.files[0]) return;
    App.qs('#archivoNombre').textContent = '📄 ' + this.files[0].name;
    const fd = new FormData();
    fd.append('materia_id', MATERIA_ID);
    fd.append('archivo', this.files[0]);
    App.showLoader && App.showLoader();
    fetch('../api/inscribir_alumnos.php', { method: 'POST', body: fd })
      .then(r => r.json())
      .then(function (d) {
        App.hideLoader && App.hideLoader();
        let msg = d.inscritos + ' alumno(s) inscripto(s).';
        if (d.ya_inscritos)    msg += ' ' + d.ya_inscritos + ' ya estaban.';
        if (d.no_encontrados?.length) msg += ' No encontrados: ' + d.no_encontrados.join(', ');
        App.toast(msg, d.inscritos > 0 ? 'success' : 'error');
        if (d.inscritos > 0) setTimeout(() => location.reload(), 1500);
      })
      .catch(() => { App.hideLoader && App.hideLoader(); App.toast('Error al importar.', 'error'); });
  });

  // ── Desinscribir ──────────────────────────────────────────────
  document.addEventListener('click', function (e) {
    const btn = e.target.closest('.btn-desinscribir');
    if (!btn) return;
    const legajo = btn.dataset.legajo;
    if (!confirm('¿Desinscribir legajo ' + legajo + ' de esta materia?')) return;
    App.api('../api/desinscribir_alumno.php', {
      method: 'POST',
      body: JSON.stringify({ materia_id: MATERIA_ID, legajo }),
    })
    .then(function () {
      btn.closest('tr').remove();
      actualizarTotal(-1);
      App.toast('Alumno desinscripto.', 'success');
    })
    .catch(err => App.toast(err.message, 'error'));
  });

  // ── Helpers DOM ───────────────────────────────────────────────
  function agregarFila(legajo, nombre, ini, curso) {
    const empty = App.qs('#emptyRow');
    if (empty) empty.remove();
    const tr = document.createElement('tr');
    tr.dataset.legajo = legajo;
    tr.innerHTML = `
      <td><div class="cell-name"><span class="mini-avatar">${ini}</span>${nombre}</div></td>
      <td>${legajo}</td><td>${curso}</td>
      <td><button class="btn btn-danger btn-sm btn-desinscribir" data-legajo="${legajo}">
        <i class="fa-solid fa-xmark"></i></button></td>`;
    App.qs('#inscriptosTable tbody').prepend(tr);
    actualizarTotal(1);
  }

  function actualizarTotal(delta) {
    const badge = App.qs('#totalBadge');
    badge.textContent = (parseInt(badge.textContent) + delta) + ' alumnos';
  }
</script>
</body>
</html>
