<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';
require_auth(['secretaria', 'admin']);

$pdo = getPDO();

$cursos = $pdo->query("SELECT nombre FROM cursos ORDER BY nombre")->fetchAll(PDO::FETCH_COLUMN);

$profesores = $pdo->query(
    "SELECT id, CONCAT(nombre,' ',apellido) AS nombre
     FROM usuarios WHERE rol='profesor' AND activo=1 ORDER BY apellido"
)->fetchAll();

// Materias con horario concatenado
$materias = $pdo->query(
    "SELECT m.id, m.nombre, m.codigo, m.curso, m.modalidad,
            COALESCE(CONCAT(u.nombre,' ',u.apellido),'—') AS profesor,
            COALESCE(CONCAT(u2.nombre,' ',u2.apellido),'') AS profesor_2,
            (SELECT GROUP_CONCAT(
               CASE dia_semana
                 WHEN 1 THEN 'Lun' WHEN 2 THEN 'Mar' WHEN 3 THEN 'Mié'
                 WHEN 4 THEN 'Jue' WHEN 5 THEN 'Vie' WHEN 6 THEN 'Sáb' ELSE 'Dom'
               END ORDER BY dia_semana SEPARATOR ', ')
             FROM materia_horarios WHERE materia_id = m.id) AS dias,
            (SELECT CONCAT(TIME_FORMAT(hora_inicio,'%H:%i'),' - ',TIME_FORMAT(hora_fin,'%H:%i'))
             FROM materia_horarios WHERE materia_id = m.id LIMIT 1) AS horario
     FROM materias m
     LEFT JOIN usuarios u  ON u.id  = m.profesor_id
     LEFT JOIN usuarios u2 ON u2.id = m.profesor_2_id
     WHERE m.activo = 1 ORDER BY m.nombre"
)->fetchAll();

$partes    = explode(' ', $_SESSION['nombre']);
$iniciales = strtoupper(substr($partes[0],0,1).substr($partes[1]??'',0,1));

$dias_semana = [1=>'Lun',2=>'Mar',3=>'Mié',4=>'Jue',5=>'Vie',6=>'Sáb',7=>'Dom'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" /><meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Materias · Secretaría</title>
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
      <a href="materias.php" class="active"><i class="fa-solid fa-book"></i> Materias</a>
      <a href="inscripciones.php"><i class="fa-solid fa-user-plus"></i> Inscripciones</a>
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
      <div class="page-title">Materias <small>Creá materias con horario semanal fijo</small></div>
    </header>
    <main class="app-content" style="padding:16px;overflow:hidden">
      <div class="mat-layout">

        <!-- ── Formulario ────────────────────────────────────── -->
        <div class="card mat-form-panel">
          <div style="padding:20px 20px 0">
            <h3 style="font-size:1.05rem;margin-bottom:3px">Nueva materia</h3>
            <p class="text-muted" style="font-size:0.84rem;margin-bottom:14px">Definí el horario semanal fijo para que el profesor sepa cuándo dicta.</p>
          </div>
          <div class="mat-form-scroll">
            <form id="materiaForm" style="padding:0 20px 20px">
              <div class="field">
                <label>Nombre de la materia</label>
                <input class="input" name="nombre" placeholder="Ej: Programación I" required />
              </div>
              <div class="field">
                <label>Profesor a cargo</label>
                <select class="select" name="profesor_id">
                  <option value="">Sin asignar</option>
                  <?php foreach ($profesores as $p): ?>
                    <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['nombre']) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="field">
                <label>Segundo profesor <span class="text-muted">(opcional)</span></label>
                <select class="select" name="profesor_2_id">
                  <option value="">Sin asignar</option>
                  <?php foreach ($profesores as $p): ?>
                    <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['nombre']) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="form-grid">
                <div class="field">
                  <label>Código <span class="text-muted">(opcional)</span></label>
                  <input class="input" name="codigo" placeholder="Ej: PRG1" />
                </div>
                <div class="field">
                  <label>Curso</label>
                  <select class="select" name="curso">
                    <?php foreach ($cursos as $c): ?>
                      <option><?= htmlspecialchars($c) ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
              </div>
              <div class="field">
                <label>Modalidad</label>
                <select class="select" name="modalidad">
                  <option value="presencial">Presencial</option>
                  <option value="virtual">Virtual</option>
                </select>
              </div>
              <div class="field">
                <label>Días de cursada</label>
                <div class="days-picker">
                  <?php foreach ($dias_semana as $num => $nombre_dia): ?>
                  <label class="day-opt">
                    <input type="checkbox" name="dias[]" value="<?= $num ?>" />
                    <span><?= $nombre_dia ?></span>
                  </label>
                  <?php endforeach; ?>
                </div>
              </div>
              <div class="form-grid">
                <div class="field">
                  <label>Hora inicio</label>
                  <input class="input" type="time" name="hora_inicio" />
                </div>
                <div class="field">
                  <label>Hora fin</label>
                  <input class="input" type="time" name="hora_fin" />
                </div>
              </div>
              <button type="submit" class="btn btn-primary btn-block mt-1">
                <i class="fa-solid fa-plus"></i> Crear materia
              </button>
            </form>
          </div>
        </div>

        <!-- ── Tabla con búsqueda y paginación ──────────────── -->
        <div class="card mat-table-panel">

          <!-- Toolbar -->
          <div style="padding:14px 16px;border-bottom:1px solid var(--c-border);display:flex;align-items:center;gap:10px;flex-shrink:0">
            <h3 style="font-size:1rem;flex:1;margin:0">Materias del período</h3>
            <div style="position:relative;flex:0 0 260px">
              <i class="fa-solid fa-magnifying-glass" style="position:absolute;left:10px;top:50%;transform:translateY(-50%);color:var(--c-text-faint);font-size:0.8rem;pointer-events:none"></i>
              <input id="searchInput" class="input" placeholder="Buscar por nombre o código..."
                     style="padding-left:30px;height:34px;font-size:0.85rem" />
            </div>
          </div>

          <!-- Tabla scrolleable -->
          <div style="flex:1;overflow-y:auto">
            <table class="data-table" id="materiasTable">
              <thead>
                <tr><th>Materia</th><th>Profesor</th><th>Días</th><th>Horario</th><th>Modalidad</th><th></th></tr>
              </thead>
              <tbody id="materiasBody"></tbody>
            </table>
          </div>

          <!-- Paginación -->
          <div id="paginacion" style="padding:10px 16px;border-top:1px solid var(--c-border);display:flex;align-items:center;justify-content:space-between;flex-shrink:0;font-size:0.85rem">
            <span id="pgInfo" class="text-muted"></span>
            <div style="display:flex;gap:4px" id="pgBtns"></div>
          </div>

        </div>
      </div>
    </main>
  </div>
</div>
<style>
/* Layout dos paneles fijo en viewport */
.mat-layout {
  display: grid;
  grid-template-columns: 360px 1fr;
  gap: 16px;
  height: calc(100vh - 96px); /* 64px topbar + 32px padding */
}
.mat-form-panel {
  display: flex;
  flex-direction: column;
  overflow: hidden;
}
.mat-form-scroll {
  flex: 1;
  overflow-y: auto;
  scrollbar-width: thin;
}
.mat-table-panel {
  display: flex;
  flex-direction: column;
  overflow: hidden;
}

/* Days picker */
.days-picker { display:flex; gap:6px; flex-wrap:wrap; }
.day-opt input { display:none; }
.day-opt span {
  display:inline-block; padding:5px 9px; border-radius:8px; font-size:0.8rem;
  font-weight:600; cursor:pointer; border:2px solid var(--c-border);
  color:var(--c-text-soft); transition:.15s;
}
.day-opt input:checked + span {
  background:var(--c-primary); color:#fff; border-color:var(--c-primary);
}

/* Paginación */
.pg-btn {
  display:inline-flex; align-items:center; justify-content:center;
  width:30px; height:30px; border-radius:7px; font-size:0.82rem;
  font-weight:600; border:1px solid var(--c-border); cursor:pointer;
  background:transparent; color:var(--c-text-soft); transition:.15s;
}
.pg-btn:hover:not(.pg-active):not(:disabled) { background:var(--c-muted-soft); }
.pg-btn.pg-active { background:var(--c-primary); color:#fff; border-color:var(--c-primary); }
.pg-btn:disabled { opacity:.35; cursor:default; }
</style>
<script src="../assets/js/utils.js"></script>
<script>
(function () {

// ── Datos de PHP → JS ─────────────────────────────────────────
let ALL = <?= json_encode(array_values($materias)) ?>;
const PER_PAGE = 10;
let filtrado = [...ALL];
let pagina   = 1;

// ── Render tabla ──────────────────────────────────────────────
function rowHtml(m) {
  const mod   = m.modalidad === 'virtual'
    ? '<span class="badge badge-muted">Virtual</span>'
    : '<span class="badge badge-accent">Presencial</span>';
  const prof2 = m.profesor_2
    ? `<br><small class="text-muted">${esc(m.profesor_2)}</small>` : '';
  const cod   = m.codigo ? `<small class="text-muted">${esc(m.codigo)} · </small>` : '';
  return `
    <td><div class="cell-name">${esc(m.nombre)}</div>
        <small class="text-muted">${cod}${esc(m.curso)}</small></td>
    <td>${esc(m.profesor)}${prof2}</td>
    <td style="white-space:nowrap">${esc(m.dias || '—')}</td>
    <td style="white-space:nowrap">${esc(m.horario || '—')}</td>
    <td>${mod}</td>
    <td style="white-space:nowrap">
      <a href="generar_clases.php?materia_id=${m.id}" class="btn btn-ghost btn-sm" title="Generar clases">
        <i class="fa-solid fa-calendar-plus"></i>
      </a>
      <button class="btn btn-ghost btn-sm" data-elim="${m.id}" data-nombre="${esc(m.nombre)}"
              title="Eliminar" style="color:var(--c-danger)">
        <i class="fa-solid fa-trash"></i>
      </button>
    </td>`;
}

function esc(s) {
  return String(s ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

function render() {
  const tbody  = document.getElementById('materiasBody');
  const inicio = (pagina - 1) * PER_PAGE;
  const items  = filtrado.slice(inicio, inicio + PER_PAGE);

  if (items.length === 0) {
    tbody.innerHTML = '<tr><td colspan="6" style="text-align:center;padding:40px;color:var(--c-text-faint)">Sin resultados.</td></tr>';
  } else {
    tbody.innerHTML = items.map(m => `<tr>${rowHtml(m)}</tr>`).join('');
    tbody.querySelectorAll('[data-elim]').forEach(btn => btn.addEventListener('click', () => confirmarEliminar(btn)));
  }

  renderPaginacion(inicio, items.length);
}

function renderPaginacion(inicio, count) {
  const total  = filtrado.length;
  const pages  = Math.max(1, Math.ceil(total / PER_PAGE));
  const fin    = inicio + count;

  document.getElementById('pgInfo').textContent =
    total === 0 ? '' : `Mostrando ${inicio + 1}–${fin} de ${total}`;

  const cont = document.getElementById('pgBtns');
  cont.innerHTML = '';

  // Prev
  const prev = document.createElement('button');
  prev.className = 'pg-btn'; prev.innerHTML = '<i class="fa-solid fa-chevron-left"></i>';
  prev.disabled = pagina <= 1;
  prev.onclick = () => { pagina--; render(); };
  cont.appendChild(prev);

  // Números (máximo 5 visibles)
  const range = pageRange(pagina, pages);
  range.forEach(p => {
    if (p === '…') {
      const sp = document.createElement('span');
      sp.className = 'pg-btn'; sp.style.cursor = 'default'; sp.textContent = '…';
      cont.appendChild(sp);
    } else {
      const btn = document.createElement('button');
      btn.className = 'pg-btn' + (p === pagina ? ' pg-active' : '');
      btn.textContent = p;
      btn.onclick = () => { pagina = p; render(); };
      cont.appendChild(btn);
    }
  });

  // Next
  const next = document.createElement('button');
  next.className = 'pg-btn'; next.innerHTML = '<i class="fa-solid fa-chevron-right"></i>';
  next.disabled = pagina >= pages;
  next.onclick = () => { pagina++; render(); };
  cont.appendChild(next);
}

function pageRange(cur, total) {
  if (total <= 7) return Array.from({length: total}, (_, i) => i + 1);
  if (cur <= 4)   return [1,2,3,4,5,'…',total];
  if (cur >= total - 3) return [1,'…',total-4,total-3,total-2,total-1,total];
  return [1,'…',cur-1,cur,cur+1,'…',total];
}

// ── Buscador ──────────────────────────────────────────────────
document.getElementById('searchInput').addEventListener('input', function () {
  const q = this.value.toLowerCase().trim();
  filtrado = q
    ? ALL.filter(m =>
        m.nombre.toLowerCase().includes(q) ||
        (m.codigo && m.codigo.toLowerCase().includes(q)) ||
        m.curso.toLowerCase().includes(q))
    : [...ALL];
  pagina = 1;
  render();
});

// ── Crear materia ─────────────────────────────────────────────
document.getElementById('materiaForm').addEventListener('submit', function (e) {
  e.preventDefault();
  const fd   = new FormData(this);
  const dias = fd.getAll('dias[]').map(Number);
  const hi   = fd.get('hora_inicio');
  const hf   = fd.get('hora_fin');

  App.api('../api/crear_materia.php', {
    method: 'POST', loader: true,
    body: JSON.stringify({
      nombre:        fd.get('nombre'),
      codigo:        fd.get('codigo'),
      curso:         fd.get('curso'),
      modalidad:     fd.get('modalidad'),
      profesor_id:   fd.get('profesor_id')   || 0,
      profesor_2_id: fd.get('profesor_2_id') || 0,
      horarios:      dias.map(d => ({ dia: d, hora_inicio: hi, hora_fin: hf })),
    }),
  })
  .then(function (res) {
    const m = res.materia;
    const nuevo = {
      id: m.id, nombre: m.nombre, codigo: m.codigo || '',
      curso: m.curso, modalidad: m.modalidad,
      profesor: m.profesor, profesor_2: m.profesor_2 || '',
      dias: m.dias, horario: m.hora,
    };
    ALL.unshift(nuevo);
    document.getElementById('searchInput').value = '';
    filtrado = [...ALL];
    pagina = 1;
    render();
    App.toast('Materia "' + m.nombre + '" creada.', 'success');
    App.qs('#materiaForm').reset();
    App.qsa('.day-opt input').forEach(c => c.checked = false);
  })
  .catch(err => App.toast(err.message, 'error'));
});

// ── Eliminar materia ──────────────────────────────────────────
function confirmarEliminar(btn) {
  const id     = parseInt(btn.dataset.elim);
  const nombre = btn.dataset.nombre;

  const overlay = document.createElement('div');
  overlay.className = 'modal-overlay';
  overlay.innerHTML = `
    <div class="modal">
      <div class="modal-head">
        <h3>Eliminar materia</h3>
        <button class="modal-close" id="cerrarModal">&times;</button>
      </div>
      <div class="modal-body">
        <p>¿Estás seguro que querés eliminar <strong>${nombre}</strong>?</p>
        <p class="text-muted" style="font-size:0.85rem;margin-top:8px">
          Se eliminarán también sus horarios e inscripciones. Las clases y asistencias registradas quedan en el historial.
        </p>
      </div>
      <div class="modal-foot">
        <button class="btn btn-ghost" id="cancelarElim">Cancelar</button>
        <button class="btn btn-danger" id="confirmarElim">
          <i class="fa-solid fa-trash"></i> Sí, eliminar
        </button>
      </div>
    </div>`;
  document.body.appendChild(overlay);
  overlay.querySelector('#cerrarModal').onclick =
  overlay.querySelector('#cancelarElim').onclick = () => overlay.remove();

  overlay.querySelector('#confirmarElim').addEventListener('click', function () {
    overlay.remove();
    App.api('../api/eliminar_materia.php', {
      method: 'POST', loader: true,
      body: JSON.stringify({ materia_id: id }),
    })
    .then(function () {
      ALL = ALL.filter(m => m.id !== id);
      filtrado = filtrado.filter(m => m.id !== id);
      if ((pagina - 1) * PER_PAGE >= filtrado.length && pagina > 1) pagina--;
      render();
      App.toast('Materia eliminada.', 'success');
    })
    .catch(err => App.toast(err.message, 'error'));
  });
}

// ── Inicio ────────────────────────────────────────────────────
render();

})();
</script>
</body>
</html>
