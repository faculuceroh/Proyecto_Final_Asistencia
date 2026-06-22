<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';
require_auth(['profesor', 'admin']);

$pdo     = getPDO();
$prof_id = $_SESSION['usuario_id'];

// Materias asignadas al profesor (para el select del formulario)
$stmt = $pdo->prepare(
    "SELECT id, CONCAT(nombre, ' · ', curso) AS label, modalidad
     FROM materias WHERE profesor_id = ? AND activo = 1 ORDER BY nombre"
);
$stmt->execute([$prof_id]);
$mis_materias = $stmt->fetchAll();

// Horarios semanales por materia → pasados a JS para auto-completar el form
$horarios_map = [];
foreach ($mis_materias as $m) {
    $sh = $pdo->prepare(
        'SELECT dia_semana,
                TIME_FORMAT(hora_inicio,"%H:%i") AS hi,
                TIME_FORMAT(hora_fin,"%H:%i")    AS hf,
                TIMESTAMPDIFF(MINUTE, hora_inicio, hora_fin) AS dur
         FROM materia_horarios WHERE materia_id = ? ORDER BY dia_semana'
    );
    $sh->execute([$m['id']]);
    $horarios_map[$m['id']] = [
        'modalidad' => $m['modalidad'],
        'horarios'  => $sh->fetchAll(),
    ];
}

// Clases del profesor (recientes y próximas)
$stmt = $pdo->prepare(
    "SELECT c.id, c.fecha, c.hora_inicio, c.duracion_min, c.aula, c.modalidad, c.estado,
            m.nombre AS materia, m.curso
     FROM clases c
     JOIN materias m ON m.id = c.materia_id
     WHERE m.profesor_id = ?
     ORDER BY c.fecha DESC, c.hora_inicio DESC
     LIMIT 30"
);
$stmt->execute([$prof_id]);
$clases = $stmt->fetchAll();

$partes    = explode(' ', $_SESSION['nombre']);
$iniciales = strtoupper(substr($partes[0],0,1).substr($partes[1]??'',0,1));

$estado_badge = [
    'pendiente'  => ['badge-muted',   'Pendiente'],
    'en_curso'   => ['badge-warning', 'En curso'],
    'finalizada' => ['badge-success', 'Finalizada'],
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" /><meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Clases · Asistencia QR</title>
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
      <div><div class="name">Asistencia QR</div><div class="sub">Portal Profesor</div></div>
    </div>
    <nav class="sidebar-nav">
      <span class="nav-label">Principal</span>
      <a href="dashboard.php"><i class="fa-solid fa-house"></i> Mis clases</a>
      <a href="materias.php" class="active"><i class="fa-solid fa-book"></i> Programar clase</a>
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
      <div class="page-title">Programar clase <small>Creá una clase para una de tus materias</small></div>
    </header>

    <main class="app-content">
      <?php if (empty($mis_materias)): ?>
        <div class="card" style="padding:32px;text-align:center;color:var(--c-text-faint)">
          <i class="fa-solid fa-triangle-exclamation" style="font-size:2rem;margin-bottom:12px;color:var(--c-warning)"></i>
          <p>Todavía no tenés materias asignadas. Pedile a secretaría que te asigne una materia.</p>
        </div>
      <?php else: ?>
      <div style="display:grid;grid-template-columns:1fr 1.5fr;gap:20px;align-items:start">

        <!-- Formulario nueva clase -->
        <div class="card" style="padding:24px">
          <h3 style="font-size:1.1rem;margin-bottom:4px">Nueva clase</h3>
          <p class="text-muted" style="font-size:0.88rem;margin-bottom:18px">
            Solo podés crear clases para tus materias asignadas.
          </p>
          <form id="claseForm">
            <div class="field">
              <label>Materia</label>
              <select class="select" name="materia_id" required>
                <option value="">— Elegí una materia —</option>
                <?php foreach ($mis_materias as $m): ?>
                  <option value="<?= $m['id'] ?>"><?= htmlspecialchars($m['label']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="form-grid">
              <div class="field">
                <label>Fecha</label>
                <input class="input" type="date" name="fecha"
                       value="<?= date('Y-m-d') ?>" required />
              </div>
              <div class="field">
                <label>Hora de inicio</label>
                <input class="input" type="time" name="hora_inicio" required />
              </div>
            </div>
            <div class="form-grid">
              <div class="field">
                <label>Duración (minutos)</label>
                <input class="input" type="number" name="duracion_min"
                       value="90" min="30" max="480" step="15" required />
              </div>
              <div class="field">
                <label>Modalidad</label>
                <select class="select" name="modalidad">
                  <option value="presencial">Presencial</option>
                  <option value="virtual">Virtual</option>
                </select>
              </div>
            </div>
            <div class="field" id="aulaField">
              <label>Aula <span class="text-muted">(opcional)</span></label>
              <input class="input" name="aula" placeholder="Ej: Aula 204" />
            </div>
            <button type="submit" class="btn btn-primary btn-block mt-1">
              <i class="fa-solid fa-plus"></i> Crear clase
            </button>
          </form>
        </div>

        <!-- Tabla de clases -->
        <div class="card table-card">
          <div class="toolbar" style="padding:16px 16px 0;margin:0">
            <h3 style="font-size:1.05rem">Clases programadas</h3>
          </div>
          <div class="table-scroll">
            <table class="data-table" id="clasesTable">
              <thead>
                <tr><th>Materia</th><th>Fecha</th><th>Hora</th><th>Aula</th><th>Estado</th></tr>
              </thead>
              <tbody>
              <?php foreach ($clases as $c):
                $b = $estado_badge[$c['estado']] ?? ['badge-muted', $c['estado']];
              ?>
                <tr>
                  <td>
                    <div class="cell-name"><?= htmlspecialchars($c['materia']) ?></div>
                    <small class="text-muted"><?= htmlspecialchars($c['curso']) ?></small>
                  </td>
                  <td><?= date('d/m/Y', strtotime($c['fecha'])) ?></td>
                  <td><?= substr($c['hora_inicio'], 0, 5) ?> <small class="text-muted"><?= $c['duracion_min'] ?>min</small></td>
                  <td><?= $c['modalidad'] === 'virtual' ? '<i class="fa-solid fa-video"></i> Virtual' : htmlspecialchars($c['aula'] ?? '—') ?></td>
                  <td><span class="badge <?= $b[0] ?>"><?= $b[1] ?></span></td>
                </tr>
              <?php endforeach; ?>
              <?php if (empty($clases)): ?>
                <tr><td colspan="5" style="text-align:center;padding:32px;color:var(--c-text-faint)">
                  No hay clases programadas todavía.
                </td></tr>
              <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>

      </div>
      <?php endif; ?>
    </main>
  </div>
</div>
<script src="../assets/js/utils.js"></script>
<script>
  // Horarios semanales por materia (inyectados desde PHP)
  const horariosMap = <?= json_encode($horarios_map) ?>;

  // Al cambiar materia: auto-completar hora y duración según horario del día
  App.qs('[name="materia_id"]').addEventListener('change', function () {
    const mid  = this.value;
    const data = horariosMap[mid];
    if (!data) return;

    // Modalidad de la materia
    App.qs('[name="modalidad"]').value = data.modalidad;
    App.qs('#aulaField').classList.toggle('hidden', data.modalidad === 'virtual');

    if (!data.horarios.length) return;

    // Intentar usar el horario del día de hoy (1=Lun ... 7=Dom)
    const jsDay = new Date().getDay();               // 0=Dom, 1=Lun...
    const dia   = jsDay === 0 ? 7 : jsDay;           // → 1=Lun ... 7=Dom
    const hoy   = data.horarios.find(h => parseInt(h.dia_semana) === dia);
    const usar  = hoy || data.horarios[0];            // fallback al primero

    App.qs('[name="hora_inicio"]').value  = usar.hi;
    App.qs('[name="duracion_min"]').value = usar.dur;
  });

  // Oculta el campo Aula cuando la modalidad es virtual
  App.qs('[name="modalidad"]').addEventListener('change', function () {
    App.qs('#aulaField').classList.toggle('hidden', this.value === 'virtual');
  });

  App.qs('#claseForm') && App.qs('#claseForm').addEventListener('submit', function (e) {
    e.preventDefault();
    const data = Object.fromEntries(new FormData(this));
    App.api('../api/crear_clase.php', {
      method: 'POST', loader: true,
      body: JSON.stringify({
        materia_id:  parseInt(data.materia_id),
        fecha:       data.fecha,
        hora_inicio: data.hora_inicio,
        duracion_min: parseInt(data.duracion_min),
        aula:        data.aula,
        modalidad:   data.modalidad,
      }),
    })
    .then(function (res) {
      const c = res.clase;
      const aulaTxt = c.modalidad === 'virtual'
        ? '<i class="fa-solid fa-video"></i> Virtual'
        : c.aula;
      const tr = document.createElement('tr');
      tr.innerHTML = `
        <td><div class="cell-name">${c.materia}</div><small class="text-muted">${c.curso}</small></td>
        <td>${c.fecha_fmt}</td>
        <td>${c.hora_inicio} <small class="text-muted">${c.duracion_min}min</small></td>
        <td>${aulaTxt}</td>
        <td><span class="badge badge-muted">Pendiente</span></td>`;
      // Quita el placeholder si es la primera fila
      const empty = App.qs('#clasesTable tbody tr td[colspan]');
      if (empty) empty.closest('tr').remove();
      App.qs('#clasesTable tbody').prepend(tr);
      App.toast('Clase creada para el ' + c.fecha_fmt + '.', 'success');
      App.qs('[name="hora_inicio"]').value = '';
      App.qs('[name="aula"]').value = '';
    })
    .catch(err => App.toast(err.message, 'error'));
  });
</script>
</body>
</html>
