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
    "SELECT m.id, m.nombre, m.curso, m.modalidad,
            COALESCE(CONCAT(u.nombre,' ',u.apellido),'—') AS profesor,
            (SELECT GROUP_CONCAT(
               CASE dia_semana
                 WHEN 1 THEN 'Lun' WHEN 2 THEN 'Mar' WHEN 3 THEN 'Mié'
                 WHEN 4 THEN 'Jue' WHEN 5 THEN 'Vie' WHEN 6 THEN 'Sáb' ELSE 'Dom'
               END ORDER BY dia_semana SEPARATOR ', ')
             FROM materia_horarios WHERE materia_id = m.id) AS dias,
            (SELECT CONCAT(TIME_FORMAT(hora_inicio,'%H:%i'),' - ',TIME_FORMAT(hora_fin,'%H:%i'))
             FROM materia_horarios WHERE materia_id = m.id LIMIT 1) AS horario
     FROM materias m
     LEFT JOIN usuarios u ON u.id = m.profesor_id
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
    <main class="app-content">
      <div style="display:grid;grid-template-columns:1fr 1.5fr;gap:20px;align-items:start">

        <!-- Formulario -->
        <div class="card" style="padding:24px">
          <h3 style="font-size:1.1rem;margin-bottom:4px">Nueva materia</h3>
          <p class="text-muted" style="font-size:0.88rem;margin-bottom:18px">Definí el horario semanal fijo para que el profesor sepa cuándo dicta.</p>
          <form id="materiaForm">
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

            <!-- Horario semanal -->
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
            <div class="form-grid" id="horaFields">
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

        <!-- Tabla -->
        <div class="card table-card">
          <div class="toolbar" style="padding:16px 16px 0;margin:0">
            <h3 style="font-size:1.05rem">Materias del período</h3>
          </div>
          <div class="table-scroll">
            <table class="data-table" id="materiasTable">
              <thead><tr><th>Materia</th><th>Profesor</th><th>Días</th><th>Horario</th><th>Modalidad</th></tr></thead>
              <tbody>
              <?php foreach ($materias as $m): ?>
                <tr>
                  <td>
                    <div class="cell-name"><?= htmlspecialchars($m['nombre']) ?></div>
                    <small class="text-muted"><?= htmlspecialchars($m['curso']) ?></small>
                  </td>
                  <td><?= htmlspecialchars($m['profesor']) ?></td>
                  <td><?= htmlspecialchars($m['dias'] ?? '—') ?></td>
                  <td><?= htmlspecialchars($m['horario'] ?? '—') ?></td>
                  <td><span class="badge <?= $m['modalidad']==='virtual'?'badge-muted':'badge-accent' ?>"><?= ucfirst($m['modalidad']) ?></span></td>
                </tr>
              <?php endforeach; ?>
              <?php if(empty($materias)): ?>
                <tr><td colspan="5" style="text-align:center;padding:32px;color:var(--c-text-faint)">No hay materias registradas.</td></tr>
              <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>

      </div>
    </main>
  </div>
</div>
<style>
.days-picker { display:flex; gap:6px; flex-wrap:wrap; }
.day-opt input { display:none; }
.day-opt span {
  display:inline-block; padding:6px 10px; border-radius:8px; font-size:0.82rem;
  font-weight:600; cursor:pointer; border:2px solid var(--c-border);
  color:var(--c-text-soft); transition:.15s;
}
.day-opt input:checked + span {
  background:var(--c-primary); color:#fff; border-color:var(--c-primary);
}
</style>
<script src="../assets/js/utils.js"></script>
<script>
  App.qs('#materiaForm').addEventListener('submit', function (e) {
    e.preventDefault();
    const fd   = new FormData(this);
    const dias = fd.getAll('dias[]').map(Number);
    const hi   = fd.get('hora_inicio');
    const hf   = fd.get('hora_fin');

    const horarios = dias.map(d => ({ dia: d, hora_inicio: hi, hora_fin: hf }));

    App.api('../api/crear_materia.php', {
      method: 'POST', loader: true,
      body: JSON.stringify({
        nombre:      fd.get('nombre'),
        codigo:      fd.get('codigo'),
        curso:       fd.get('curso'),
        modalidad:   fd.get('modalidad'),
        profesor_id: fd.get('profesor_id') || 0,
        horarios:    horarios,
      }),
    })
    .then(function (res) {
      const m    = res.materia;
      const mod  = m.modalidad === 'virtual'
        ? '<span class="badge badge-muted">Virtual</span>'
        : '<span class="badge badge-accent">Presencial</span>';
      const tr   = document.createElement('tr');
      tr.innerHTML = `
        <td><div class="cell-name">${m.nombre}</div><small class="text-muted">${m.curso}</small></td>
        <td>${m.profesor}</td>
        <td>${m.dias}</td>
        <td>${m.hora}</td>
        <td>${mod}</td>`;
      const empty = App.qs('#materiasTable tbody td[colspan]');
      if (empty) empty.closest('tr').remove();
      App.qs('#materiasTable tbody').prepend(tr);
      App.toast('Materia "' + m.nombre + '" creada.', 'success');
      App.qs('#materiaForm').reset();
      App.qsa('.day-opt input').forEach(c => c.checked = false);
    })
    .catch(err => App.toast(err.message, 'error'));
  });
</script>
</body>
</html>
