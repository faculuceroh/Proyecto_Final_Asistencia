<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" /><meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Editar asistencia &middot; Asistencia QR</title>
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
  <link rel="stylesheet" href="../assets/css/main.css" />
  <link rel="stylesheet" href="../assets/css/dashboard.css" />
  <style>
    .alumno-row { display:grid; grid-template-columns:auto 1fr auto; align-items:center; gap:12px; padding:14px 0; border-bottom:1px solid var(--c-border); }
    .alumno-row:last-child { border-bottom:none; }
    .estado-badge { min-width:90px; text-align:center; }
    .row-saved { background:var(--c-success-soft,#f0fdf4); border-radius:8px; transition:background .6s; }
    .row-check, .check-all { width:18px; height:18px; cursor:pointer; }
    .bulk-bar {
      display:flex; align-items:center; gap:12px; flex-wrap:wrap;
      padding:12px 20px; margin:0 0 8px; background:var(--c-primary-soft,#eef2ff);
      border-bottom:1px solid var(--c-border);
    }
  </style>
</head>
<body>
<div class="app-layout">
  <aside class="sidebar">
    <div class="sidebar-brand">
      <img src="../assets/img/logo.png" alt="Logo" />
      <div><div class="name">Asistencia QR</div><div class="sub">Portal Profesor</div></div>
    </div>
    <nav class="sidebar-nav">
      <span class="nav-label">Principal</span>
      <a href="dashboard.php"><i class="fa-solid fa-house"></i> Mis clases</a>
      <a href="historial.php"><i class="fa-solid fa-clock-rotate-left"></i> Historial</a>
      <span class="nav-label">Cuenta</span>
      <a href="perfil.php"><i class="fa-solid fa-user"></i> Mi perfil</a>
      <a href="../logout.php"><i class="fa-solid fa-right-from-bracket"></i> Cerrar sesión</a>
    </nav>
    <div class="sidebar-user">
      <div class="avatar"><?= htmlspecialchars($iniciales) ?></div>
      <div class="meta">
        <div class="u-name"><?= htmlspecialchars($_SESSION['nombre']) ?></div>
        <div class="u-role">Profesor</div>
      </div>
      <a href="../logout.php" class="logout"><i class="fa-solid fa-right-from-bracket"></i></a>
    </div>
  </aside>
  <div class="sidebar-backdrop" data-sidebar-backdrop></div>

  <div class="app-main">
    <header class="topbar">
      <button class="hamburger" data-sidebar-toggle aria-label="Menú"><i class="fa-solid fa-bars"></i></button>
      <div class="page-title">
        <?= htmlspecialchars($clase['materia']) ?>
        <small><?= htmlspecialchars($clase['curso']) ?> &middot; <?= substr($clase['hora_inicio'],0,5) ?> (<?= $clase['duracion_min'] ?> min)</small>
      </div>
      <div class="topbar-right" style="display:flex;align-items:center;gap:10px">
        <span class="badge <?= $badge_clase ?>"><?= $label_clase ?></span>
        <a href="dashboard.php" class="btn btn-ghost btn-sm"><i class="fa-solid fa-arrow-left"></i> Volver</a>
      </div>
    </header>

    <main class="app-content">

      <div class="card" style="padding:24px;margin-bottom:20px;display:flex;align-items:center;gap:12px">
        <i class="fa-solid fa-circle-info" style="color:var(--c-primary);font-size:1.1rem"></i>
        <p style="font-size:0.9rem;margin:0">
          Usá esta pantalla para registrar o corregir la asistencia de alumnos que no pudieron escanear el QR.
          Tildá el checkbox de los alumnos, elegí el estado y guardá los cambios.
        </p>
      </div>

      <div class="card table-card">
        <div class="toolbar" style="padding:16px 16px 0;margin-bottom:8px">
          <h3 style="font-size:1rem">
            Listado de alumnos
            <span class="text-muted" style="font-weight:400;font-size:0.88rem">&mdash; <?= count($alumnos) ?> inscriptos</span>
          </h3>
          <span class="spacer"></span>
          <button class="btn btn-primary btn-sm" id="btnGuardarTodo">
            <i class="fa-solid fa-floppy-disk"></i> Guardar cambios
          </button>
        </div>

        <!-- Barra de selección masiva -->
        <div class="bulk-bar hidden" id="bulkBar">
          <span><strong id="selCount">0</strong> seleccionados</span>
          <select class="select" id="bulkEstado" style="min-width:130px">
            <option value="presente">Presente</option>
            <option value="tardanza">Tardanza</option>
            <option value="ausente">Ausente</option>
          </select>
          <button class="btn btn-ghost btn-sm" id="btnCancelarSeleccion">Cancelar selección</button>
        </div>

        <!-- Cabecera -->
        <div style="display:grid;grid-template-columns:auto 1fr auto;gap:12px;padding:8px 20px;background:var(--c-muted-soft);border-bottom:1px solid var(--c-border);font-size:0.8rem;font-weight:700;color:var(--c-text-soft);text-transform:uppercase;letter-spacing:.04em">
          <input type="checkbox" class="check-all" id="checkAll" title="Seleccionar todos" />
          <span>Alumno</span>
          <span style="min-width:90px">Estado</span>
        </div>

        <div id="alumnosList" style="padding:0 20px">
          <?php foreach ($alumnos as $al): ?>
          <div class="alumno-row" id="row-<?= $al['id'] ?>" data-alumno="<?= $al['id'] ?>">
            <input type="checkbox" class="row-check" data-alumno="<?= $al['id'] ?>" />

            <div>
              <div style="font-weight:600"><?= htmlspecialchars($al['apellido'].', '.$al['nombre']) ?></div>
              <div style="font-size:0.8rem;color:var(--c-text-faint)">Legajo <?= htmlspecialchars($al['legajo']) ?></div>
            </div>

            <span class="badge estado-badge <?= $badge[$al['estado']] ?? 'badge-muted' ?>" data-field="estado" data-estado="<?= $al['estado'] ?>">
              <?= ucfirst($al['estado']) ?>
            </span>
          </div>
          <?php endforeach; ?>

          <?php if (empty($alumnos)): ?>
            <div style="padding:40px;text-align:center;color:var(--c-text-faint)">
              No hay alumnos inscriptos en esta materia.
            </div>
          <?php endif; ?>
        </div>
      </div>

    </main>
  </div>
</div>

<script src="../assets/js/utils.js"></script>
<script>
const CLASE_ID = <?= $clase_id ?>;

const ESTADO_BADGE = {
  presente: 'badge-success',
  tardanza: 'badge-warning',
  ausente:  'badge-danger',
};
const ESTADO_LABEL = {
  presente: 'Presente',
  tardanza: 'Tardanza',
  ausente:  'Ausente',
};

function markSaved(row) {
  row.classList.add('row-saved');
  setTimeout(() => row.classList.remove('row-saved'), 1200);
}

function actualizarBadge(alumnoId, estado) {
  const row = document.getElementById('row-' + alumnoId);
  if (!row) return;
  const badgeEl = row.querySelector('[data-field="estado"]');
  badgeEl.textContent = ESTADO_LABEL[estado] || estado;
  badgeEl.className = 'badge estado-badge ' + (ESTADO_BADGE[estado] || 'badge-muted');
  badgeEl.dataset.estado = estado;
  markSaved(row);
}

async function guardarLote(cambios, boton) {
  if (!cambios.length) return;
  const textoOriginal = boton.innerHTML;
  boton.disabled = true;
  boton.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Guardando…';

  try {
    const res = await App.api('../api/editar_asistencia_lote.php', {
      method: 'POST',
      body: JSON.stringify({ clase_id: CLASE_ID, cambios }),
      loader: true,
    });

    cambios.forEach(c => actualizarBadge(c.alumno_id, c.estado));
    App.toast(res.actualizados + ' alumno(s) actualizados', 'success');
  } catch (err) {
    App.toast(err.message, 'error');
  } finally {
    boton.disabled = false;
    boton.innerHTML = textoOriginal;
  }
}

/* ---------- Selección masiva ---------- */
const checkAll   = document.getElementById('checkAll');
const bulkBar    = document.getElementById('bulkBar');
const selCount   = document.getElementById('selCount');
const bulkEstado = document.getElementById('bulkEstado');
const btnGuardarTodo       = document.getElementById('btnGuardarTodo');
const btnCancelarSeleccion = document.getElementById('btnCancelarSeleccion');

function rowChecks() {
  return [...document.querySelectorAll('.row-check')];
}

function actualizarBarraSeleccion() {
  const marcados = rowChecks().filter(c => c.checked);
  selCount.textContent = marcados.length;
  bulkBar.classList.toggle('hidden', marcados.length === 0);
  if (checkAll) {
    checkAll.checked = marcados.length > 0 && marcados.length === rowChecks().length;
  }
}

if (checkAll) {
  checkAll.addEventListener('change', () => {
    rowChecks().forEach(c => (c.checked = checkAll.checked));
    actualizarBarraSeleccion();
  });
}

document.querySelectorAll('.row-check').forEach(c => {
  c.addEventListener('change', actualizarBarraSeleccion);
});

if (btnCancelarSeleccion) {
  btnCancelarSeleccion.addEventListener('click', () => {
    rowChecks().forEach(c => (c.checked = false));
    actualizarBarraSeleccion();
  });
}

/* ---------- Guardar cambios: aplica el estado elegido a los seleccionados ---------- */
if (btnGuardarTodo) {
  btnGuardarTodo.addEventListener('click', () => {
    const marcados = rowChecks().filter(c => c.checked);
    if (!marcados.length) {
      App.toast('Tildá al menos un alumno para guardar', 'error');
      return;
    }
    const estado = bulkEstado.value;
    const cambios = marcados.map(c => ({
      alumno_id: parseInt(c.dataset.alumno, 10),
      estado,
    }));

    guardarLote(cambios, btnGuardarTodo).then(() => {
      rowChecks().forEach(c => (c.checked = false));
      actualizarBarraSeleccion();
    });
  });
}
</script>
</body>
</html>
