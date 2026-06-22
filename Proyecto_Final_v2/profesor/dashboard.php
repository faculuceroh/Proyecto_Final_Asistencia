<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';
require_auth(['profesor']);

$pdo        = getPDO();
$profesor_id = $_SESSION['usuario_id'];

// Clases del día del profesor con conteo de presentes
$stmt = $pdo->prepare(
    'SELECT c.id, c.hora_inicio, c.duracion_min, c.aula, c.modalidad, c.estado,
            m.nombre AS materia, m.curso,
            (SELECT COUNT(*) FROM inscripciones WHERE materia_id = m.id) AS total_alumnos,
            (SELECT COUNT(*) FROM asistencias WHERE clase_id = c.id AND hora_entrada IS NOT NULL) AS presentes
     FROM clases c
     JOIN materias m ON m.id = c.materia_id
     WHERE m.profesor_id = ? AND c.fecha = CURDATE()
     ORDER BY c.hora_inicio ASC'
);
$stmt->execute([$profesor_id]);
$clases = $stmt->fetchAll();

// Helpers de presentación
$estado_class = [
    'en_curso'   => 'state-encurso',
    'pendiente'  => 'state-pendiente',
    'finalizada' => 'state-finalizada',
];
$estado_badge = [
    'en_curso'   => ['badge-warning', 'En curso'],
    'pendiente'  => ['badge-muted',   'Pendiente'],
    'finalizada' => ['badge-success', 'Finalizada'],
];

$dias      = ['domingo','lunes','martes','miércoles','jueves','viernes','sábado'];
$fecha_hoy = ucfirst($dias[date('w')]) . ' ' . date('d/m/Y');

$partes    = explode(' ', $_SESSION['nombre']);
$iniciales = strtoupper(substr($partes[0], 0, 1) . substr($partes[1] ?? '', 0, 1));

$en_curso  = count(array_filter($clases, fn($c) => $c['estado'] === 'en_curso'));
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Panel del Profesor · Asistencia QR</title>
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
      <a href="dashboard.php" class="active"><i class="fa-solid fa-house"></i> Mis clases</a>
      <a href="materias.php"><i class="fa-solid fa-book"></i> Programar clase</a>
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
        Hola, <?= htmlspecialchars($partes[0]) ?> 👋
        <small>Estas son tus clases de hoy</small>
      </div>
      <div class="topbar-right">
        <span class="topbar-date"><i class="fa-regular fa-calendar"></i> <?= $fecha_hoy ?></span>
      </div>
    </header>

    <main class="app-content">
      <div class="page-head">
        <div>
          <h2>Clases del día</h2>
          <p>
            <?= count($clases) ?> clases programadas
            <?= $en_curso ? "· $en_curso en curso" : '' ?>
          </p>
        </div>
      </div>

      <div class="class-list">
        <?php if (empty($clases)): ?>
          <div class="empty-state">
            <i class="fa-solid fa-calendar-xmark"></i>
            <p>No tenés clases programadas para hoy.</p>
          </div>
        <?php endif; ?>

        <?php foreach ($clases as $c): ?>
          <?php
            [$badge_cls, $badge_txt] = $estado_badge[$c['estado']] ?? ['badge-muted', $c['estado']];
            $card_cls = $estado_class[$c['estado']] ?? '';
            $hora     = substr($c['hora_inicio'], 0, 5);
            $finalizada = $c['estado'] === 'finalizada';
            if ($finalizada) {
                $badge_txt = "Finalizada · {$c['presentes']}/{$c['total_alumnos']}";
            }
          ?>
          <article class="class-card <?= $card_cls ?>">
            <div class="class-time">
              <div class="hh"><?= $hora ?></div>
              <div class="dur"><?= $c['duracion_min'] ?> min</div>
            </div>
            <div class="class-info">
              <h3><?= htmlspecialchars($c['materia']) ?></h3>
              <div class="meta">
                <?php if ($c['modalidad'] === 'presencial' && $c['aula']): ?>
                  <span><i class="fa-solid fa-location-dot"></i> <?= htmlspecialchars($c['aula']) ?></span>
                <?php else: ?>
                  <span><i class="fa-solid fa-video"></i> Virtual</span>
                <?php endif; ?>
                <span><i class="fa-solid fa-users"></i> <?= htmlspecialchars($c['curso']) ?></span>
                <span class="badge <?= $badge_cls ?>"><?= htmlspecialchars($badge_txt) ?></span>
              </div>
            </div>
            <div class="class-actions">
              <?php if ($finalizada): ?>
                <button class="btn btn-ghost btn-sm" disabled>
                  <i class="fa-solid fa-qrcode"></i> Tomar asistencia
                </button>
              <?php else: ?>
                <button class="btn btn-accent btn-sm" data-qr-open
                  data-clase="<?= $c['id'] ?>"
                  data-materia="<?= htmlspecialchars($c['materia']) ?>"
                  data-grupo="<?= htmlspecialchars($c['curso']) ?>"
                  data-modalidad="<?= $c['modalidad'] ?>"
                  data-aula="<?= htmlspecialchars($c['aula'] ?? '') ?>">
                  <i class="fa-solid fa-qrcode"></i> Tomar asistencia
                </button>
              <?php endif; ?>
              <a href="historial.php" class="btn <?= $finalizada ? 'btn-primary' : 'btn-ghost' ?> btn-sm">
                <i class="fa-solid fa-eye"></i> Ver historial
              </a>
            </div>
          </article>
        <?php endforeach; ?>
      </div>
    </main>
  </div>
</div>

<!-- Modal: configurar QR -->
<div class="modal-overlay hidden" id="qrModal">
  <div class="modal" role="dialog" aria-modal="true" aria-labelledby="qrModalTitle">
    <div class="modal-head">
      <h3 id="qrModalTitle">Generar QR de asistencia</h3>
      <button class="modal-close" data-modal-close aria-label="Cerrar">&times;</button>
    </div>
    <div class="modal-body">
      <span class="modal-clase"><i class="fa-solid fa-book"></i> <span id="modalClase">—</span></span>
      <label class="modal-label">Modalidad</label>
      <div class="seg" id="segModalidad">
        <label><input type="radio" name="modalidad" value="presencial" checked />
          <span><i class="fa-solid fa-location-dot"></i> Presencial</span></label>
        <label><input type="radio" name="modalidad" value="virtual" />
          <span><i class="fa-solid fa-video"></i> Virtual</span></label>
      </div>
      <div id="aulaField">
        <label class="modal-label">Aula</label>
        <input class="input" id="modalAula" placeholder="Ej: Aula 204" />
      </div>
      <label class="modal-label">Tipo de QR</label>
      <div class="seg seg-tipo" id="segTipo">
        <label><input type="radio" name="tipo" value="entrada" checked />
          <span><i class="fa-solid fa-right-to-bracket"></i> Entrada</span></label>
        <label><input type="radio" name="tipo" value="salida" />
          <span><i class="fa-solid fa-right-from-bracket"></i> Salida</span></label>
      </div>
    </div>
    <div class="modal-foot">
      <button class="btn btn-ghost" data-modal-close>Cancelar</button>
      <button class="btn btn-accent" id="genQrBtn"><i class="fa-solid fa-qrcode"></i> Generar QR</button>
    </div>
  </div>
</div>

<script src="../assets/js/utils.js"></script>
<script>
  (function () {
    const modal    = App.qs('#qrModal');
    const aulaField = App.qs('#aulaField');
    const aulaInput = App.qs('#modalAula');
    let current    = {};

    function toggleAula() {
      const virtual = App.qs('#segModalidad input:checked').value === 'virtual';
      aulaField.classList.toggle('hidden', virtual);
    }
    function openModal(btn) {
      current = { clase: btn.dataset.clase, materia: btn.dataset.materia, grupo: btn.dataset.grupo };
      App.qs('#modalClase').textContent = btn.dataset.materia + ' · ' + btn.dataset.grupo;
      const mod = btn.dataset.modalidad === 'virtual' ? 'virtual' : 'presencial';
      App.qs(`#segModalidad input[value="${mod}"]`).checked = true;
      aulaInput.value = btn.dataset.aula || '';
      toggleAula();
      App.qs('#segTipo input[value="entrada"]').checked = true;
      modal.classList.remove('hidden');
    }
    function closeModal() { modal.classList.add('hidden'); }

    App.qsa('[data-qr-open]').forEach(b => b.addEventListener('click', () => openModal(b)));
    App.qsa('[data-modal-close]').forEach(b => b.addEventListener('click', closeModal));
    App.qsa('#segModalidad input').forEach(r => r.addEventListener('change', toggleAula));
    modal.addEventListener('click', e => { if (e.target === modal) closeModal(); });
    document.addEventListener('keydown', e => { if (e.key === 'Escape') closeModal(); });

    App.qs('#genQrBtn').addEventListener('click', () => {
      const modalidad = App.qs('#segModalidad input:checked').value;
      const tipo      = App.qs('#segTipo input:checked').value;
      const params    = new URLSearchParams({
        clase: current.clase, materia: current.materia,
        grupo: current.grupo, modalidad, tipo,
      });
      if (modalidad === 'presencial' && aulaInput.value.trim()) {
        params.set('aula', aulaInput.value.trim());
      }
      window.location.href = 'generar_qr.php?' + params.toString();
    });
  })();
</script>
</body>
</html>
