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
                <?= htmlspecialchars($m['label'] ?? ($m['nombre'] . ' · ' . $m['curso'])) ?>
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
            <form id="manualForm">
              <div class="field">
                <label>Buscar alumno por nombre o legajo</label>
                <select class="select" name="alumno_id" id="alumnoSelect" required>
                  <option value="">— Seleccioná un alumno —</option>
                  <?php foreach ($todos_alumnos as $a): ?>
                    <option value="<?= $a['id'] ?>" data-legajo="<?= $a['legajo'] ?>">
                      <?= htmlspecialchars($a['nombre_completo'] ?? ($a['apellido'] . ', ' . $a['nombre'])) ?> · <?= $a['legajo'] ?>
                    </option>
                  <?php endforeach; ?>
                </select>
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

  // ── Alta manual ───────────────────────────────────────────────
  App.qs('#manualForm').addEventListener('submit', function (e) {
    e.preventDefault();
    const alumno_id = parseInt(App.qs('#alumnoSelect').value);
    const legajo    = App.qs('#alumnoSelect').selectedOptions[0]?.dataset.legajo;
    if (!alumno_id) return;

    App.api('../api/inscribir_alumnos.php', {
      method: 'POST', loader: true,
      body: JSON.stringify({ materia_id: MATERIA_ID, legajos: [legajo] }),
    })
    .then(function (res) {
      if (res.inscritos > 0) {
        const opt  = App.qs('#alumnoSelect').selectedOptions[0];
        const txt  = opt.textContent.trim();
        const partes = txt.split('·');
        const nombre = partes[0].trim();
        const ini  = nombre.split(',').map(p=>p.trim()).reverse().map(p=>p[0]?.toUpperCase()||'').join('');
        agregarFila(legajo, nombre, ini, '—');
        App.toast('Alumno inscripto.', 'success');
      } else if (res.ya_inscritos > 0) {
        App.toast('El alumno ya estaba inscripto.', 'error');
      } else {
        App.toast('No se encontró el alumno.', 'error');
      }
      App.qs('#manualForm').reset();
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
