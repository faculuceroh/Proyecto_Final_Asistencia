<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';
require_auth(['profesor', 'admin']);

$pdo      = getPDO();
$prof_id  = $_SESSION['usuario_id'];
$clase_id = (int)($_GET['clase_id'] ?? 0);

if (!$clase_id) { header('Location: dashboard.php'); exit; }

// Verificar que la clase es de hoy y pertenece al profesor
$stmt = $pdo->prepare(
    'SELECT c.id, c.estado, c.hora_inicio, c.duracion_min,
            m.id AS materia_id, m.nombre AS materia, m.curso
     FROM clases c
     JOIN materias m ON m.id = c.materia_id
     WHERE c.id = ? AND c.fecha = CURDATE()
       AND (m.profesor_id = ? OR m.profesor_2_id = ?)
     LIMIT 1'
);
$stmt->execute([$clase_id, $prof_id, $prof_id]);
$clase = $stmt->fetch();

if (!$clase) { header('Location: dashboard.php'); exit; }

// Alumnos inscriptos con su estado de asistencia actual
$stmt = $pdo->prepare(
    'SELECT u.id, u.legajo, u.nombre, u.apellido,
            COALESCE(a.estado, "ausente")        AS estado,
            TIME_FORMAT(a.hora_entrada, "%H:%i") AS hora_entrada,
            TIME_FORMAT(a.hora_salida,  "%H:%i") AS hora_salida
     FROM inscripciones i
     JOIN usuarios u ON u.id = i.alumno_id
     LEFT JOIN asistencias a ON a.alumno_id = i.alumno_id AND a.clase_id = ?
     WHERE i.materia_id = ?
     ORDER BY u.apellido, u.nombre'
);
$stmt->execute([$clase_id, $clase['materia_id']]);
$alumnos = $stmt->fetchAll();

$badge = [
    'presente' => 'badge-success',
    'tardanza' => 'badge-warning',
    'ausente'  => 'badge-muted',
];

$partes    = explode(' ', $_SESSION['nombre']);
$iniciales = strtoupper(substr($partes[0],0,1).substr($partes[1]??'',0,1));

$estado_label = [
    'pendiente'  => ['badge-muted',    'Pendiente'],
    'en_curso'   => ['badge-warning',  'En curso'],
    'finalizada' => ['badge-success',  'Finalizada'],
];
[$badge_clase, $label_clase] = $estado_label[$clase['estado']] ?? ['badge-muted', $clase['estado']];
?>
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
    .alumno-row { display:grid; grid-template-columns:1fr auto auto auto; align-items:center; gap:12px; padding:14px 0; border-bottom:1px solid var(--c-border); }
    .alumno-row:last-child { border-bottom:none; }
    .estado-select { min-width:130px; }
    .hora-input { width:96px; }
    .save-btn { white-space:nowrap; }
    .row-saved { background:var(--c-success-soft,#f0fdf4); border-radius:8px; transition:background .6s; }
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
      <a href="../logout.php"><i class="fa-solid fa-right-from-bracket"></i> Cerrar sesi&oacute;n</a>
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
      <button class="hamburger" data-sidebar-toggle aria-label="Men&uacute;"><i class="fa-solid fa-bars"></i></button>
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
          Usá esta pantalla para registrar o corregir la asistencia de un alumno que no pudo escanear el QR.
          Los cambios se guardan de forma individual al hacer clic en <strong>Guardar</strong>.
        </p>
      </div>

      <div class="card table-card">
        <div class="toolbar" style="padding:16px 16px 0;margin-bottom:8px">
          <h3 style="font-size:1rem">
            Listado de alumnos
            <span class="text-muted" style="font-weight:400;font-size:0.88rem">&mdash; <?= count($alumnos) ?> inscriptos</span>
          </h3>
          <div style="display:flex;gap:8px">
            <span id="savedCount" class="text-muted" style="font-size:0.85rem;align-self:center"></span>
          </div>
        </div>

        <!-- Cabecera -->
        <div style="display:grid;grid-template-columns:1fr auto auto auto;gap:12px;padding:8px 20px;background:var(--c-muted-soft);border-bottom:1px solid var(--c-border);font-size:0.8rem;font-weight:700;color:var(--c-text-soft);text-transform:uppercase;letter-spacing:.04em">
          <span>Alumno</span>
          <span style="min-width:130px">Estado</span>
          <span style="width:96px">Hora entrada</span>
          <span></span>
        </div>

        <div id="alumnosList" style="padding:0 20px">
          <?php foreach ($alumnos as $al): ?>
          <div class="alumno-row" id="row-<?= $al['id'] ?>" data-alumno="<?= $al['id'] ?>">
            <div>
              <div style="font-weight:600"><?= htmlspecialchars($al['apellido'].', '.$al['nombre']) ?></div>
              <div style="font-size:0.8rem;color:var(--c-text-faint)">Legajo <?= htmlspecialchars($al['legajo']) ?></div>
            </div>

            <select class="select estado-select" data-field="estado">
              <option value="ausente"  <?= $al['estado']==='ausente'  ?'selected':'' ?>>Ausente</option>
              <option value="presente" <?= $al['estado']==='presente' ?'selected':'' ?>>Presente</option>
              <option value="tardanza" <?= $al['estado']==='tardanza' ?'selected':'' ?>>Tardanza</option>
            </select>

            <input class="input hora-input" type="time" data-field="hora"
                   value="<?= htmlspecialchars($al['hora_entrada'] ?? '') ?>"
                   placeholder="--:--" />

            <button class="btn btn-primary btn-sm save-btn" onclick="guardar(<?= $al['id'] ?>, <?= $clase_id ?>)">
              <i class="fa-solid fa-floppy-disk"></i> Guardar
            </button>
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
let savedTotal = 0;

async function guardar(alumnoId, claseId) {
  const row      = document.getElementById('row-' + alumnoId);
  const estado   = row.querySelector('[data-field="estado"]').value;
  const hora     = row.querySelector('[data-field="hora"]').value;
  const btn      = row.querySelector('.save-btn');

  btn.disabled = true;
  btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i>';

  try {
    await App.api('../api/editar_asistencia.php', {
      method: 'POST',
      body: JSON.stringify({ clase_id: claseId, alumno_id: alumnoId, estado, hora_entrada: hora }),
    });

    // Feedback visual en la fila
    row.classList.add('row-saved');
    setTimeout(() => row.classList.remove('row-saved'), 1200);
    savedTotal++;
    document.getElementById('savedCount').textContent = savedTotal === 1
      ? '1 cambio guardado' : savedTotal + ' cambios guardados';

    App.toast('Asistencia actualizada', 'success');
  } catch (err) {
    App.toast(err.message, 'error');
  } finally {
    btn.disabled = false;
    btn.innerHTML = '<i class="fa-solid fa-floppy-disk"></i> Guardar';
  }
}
</script>
</body>
</html>
