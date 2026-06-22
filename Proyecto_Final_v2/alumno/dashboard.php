<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';
require_auth(['alumno']);

$pdo    = getPDO();
$alu_id = $_SESSION['usuario_id'];

// ── Stats globales ────────────────────────────────────────────
$prom = $pdo->prepare(
    "SELECT ROUND(SUM(estado IN ('presente','tardanza'))/NULLIF(COUNT(*),0)*100,1)
     FROM asistencias WHERE alumno_id = ?"
);
$prom->execute([$alu_id]);
$pct_global = $prom->fetchColumn() ?? 0;

$stmt = $pdo->prepare("SELECT COUNT(*) FROM asistencias WHERE alumno_id = ? AND estado IN ('presente','tardanza')");
$stmt->execute([$alu_id]);
$presentes = (int) $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM asistencias WHERE alumno_id = ? AND estado = 'ausente'");
$stmt->execute([$alu_id]);
$ausentes = (int) $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM inscripciones WHERE alumno_id = ?");
$stmt->execute([$alu_id]);
$total_materias = (int) $stmt->fetchColumn();

// ── Clases de hoy ─────────────────────────────────────────────
$stmt = $pdo->prepare(
    "SELECT c.id, c.hora_inicio, c.duracion_min, c.aula, c.modalidad, c.estado,
            m.nombre AS materia, m.curso,
            COALESCE(CONCAT(u.nombre,' ',u.apellido),'—') AS profesor,
            a.estado   AS mi_estado,
            TIME_FORMAT(a.hora_entrada,'%H:%i') AS entrada,
            TIME_FORMAT(a.hora_salida, '%H:%i') AS salida
     FROM inscripciones i
     JOIN materias m ON m.id = i.materia_id
     LEFT JOIN usuarios u ON u.id = m.profesor_id
     LEFT JOIN clases c ON c.materia_id = m.id AND c.fecha = CURDATE()
     LEFT JOIN asistencias a ON a.clase_id = c.id AND a.alumno_id = i.alumno_id
     WHERE i.alumno_id = ?
     ORDER BY c.hora_inicio ASC, m.nombre ASC"
);
$stmt->execute([$alu_id]);
$clases_hoy = $stmt->fetchAll();

// ── Asistencia reciente (últimos 5 registros) ─────────────────
$stmt = $pdo->prepare(
    "SELECT m.nombre AS materia,
            COALESCE(CONCAT(u.nombre,' ',u.apellido),'—') AS profesor,
            c.fecha,
            COALESCE(TIME_FORMAT(a.hora_entrada,'%H:%i'),'—') AS entrada,
            COALESCE(TIME_FORMAT(a.hora_salida, '%H:%i'),'—') AS salida,
            a.estado
     FROM asistencias a
     JOIN clases c ON c.id = a.clase_id
     JOIN materias m ON m.id = c.materia_id
     LEFT JOIN usuarios u ON u.id = m.profesor_id
     WHERE a.alumno_id = ?
     ORDER BY c.fecha DESC, a.updated_at DESC
     LIMIT 5"
);
$stmt->execute([$alu_id]);
$recientes = $stmt->fetchAll();

// ── Helpers ───────────────────────────────────────────────────
$estado_class = ['en_curso' => 'state-encurso', 'pendiente' => 'state-pendiente', 'finalizada' => 'state-finalizada'];
$badge_est    = ['presente' => ['badge-success','Presente'], 'tardanza' => ['badge-warning','Tardanza'], 'ausente' => ['badge-danger','Ausente']];
$partes       = explode(' ', $_SESSION['nombre']);
$iniciales    = strtoupper(substr($partes[0],0,1).substr($partes[1]??'',0,1));
$nombre_corto = $partes[0];

// Info del alumno (curso y legajo para sidebar)
$stmt = $pdo->prepare('SELECT legajo, curso FROM usuarios WHERE id = ? LIMIT 1');
$stmt->execute([$alu_id]);
$alumno = $stmt->fetch();
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" /><meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Mi panel · Asistencia QR</title>
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
      <div><div class="name">Asistencia QR</div><div class="sub">Portal Alumno</div></div>
    </div>
    <nav class="sidebar-nav">
      <span class="nav-label">Principal</span>
      <a href="dashboard.php" class="active"><i class="fa-solid fa-house"></i> Inicio</a>
      <a href="materias.php"><i class="fa-solid fa-book"></i> Mis materias</a>
      <a href="escanear.php"><i class="fa-solid fa-qrcode"></i> Escanear QR</a>
      <a href="historial.php"><i class="fa-solid fa-clock-rotate-left"></i> Mi asistencia</a>
      <span class="nav-label">Cuenta</span>
      <a href="perfil.php"><i class="fa-solid fa-user"></i> Mi perfil</a>
      <a href="../logout.php"><i class="fa-solid fa-right-from-bracket"></i> Cerrar sesión</a>
    </nav>
    <div class="sidebar-user">
      <div class="avatar"><?= htmlspecialchars($iniciales) ?></div>
      <div class="meta">
        <div class="u-name"><?= htmlspecialchars($_SESSION['nombre']) ?></div>
        <div class="u-role">Legajo <?= htmlspecialchars($alumno['legajo']) ?><?= $alumno['curso'] ? ' · '.$alumno['curso'] : '' ?></div>
      </div>
      <a href="../logout.php" class="logout"><i class="fa-solid fa-right-from-bracket"></i></a>
    </div>
  </aside>
  <div class="sidebar-backdrop" data-sidebar-backdrop></div>

  <div class="app-main">
    <header class="topbar">
      <button class="hamburger" data-sidebar-toggle aria-label="Menú"><i class="fa-solid fa-bars"></i></button>
      <div class="page-title">Hola, <?= htmlspecialchars($nombre_corto) ?> <small>Este es tu resumen de asistencia</small></div>
      <div class="topbar-right">
        <a href="escanear.php" class="btn btn-accent btn-sm"><i class="fa-solid fa-qrcode"></i> Escanear QR</a>
      </div>
    </header>

    <main class="app-content">
      <!-- Stats -->
      <div class="stat-grid">
        <div class="stat-card"><div class="stat-icon i-green"><i class="fa-solid fa-percent"></i></div><div><div class="stat-value"><?= $pct_global ?>%</div><div class="stat-label">Mi asistencia</div></div></div>
        <div class="stat-card"><div class="stat-icon i-blue"><i class="fa-solid fa-user-check"></i></div><div><div class="stat-value"><?= $presentes ?></div><div class="stat-label">Clases presentes</div></div></div>
        <div class="stat-card"><div class="stat-icon i-amber"><i class="fa-solid fa-user-xmark"></i></div><div><div class="stat-value"><?= $ausentes ?></div><div class="stat-label">Ausencias</div></div></div>
        <div class="stat-card"><div class="stat-icon i-navy"><i class="fa-solid fa-book"></i></div><div><div class="stat-value"><?= $total_materias ?></div><div class="stat-label">Materias</div></div></div>
      </div>

      <!-- Clases de hoy -->
      <div class="page-head">
        <div><h2>Clases de hoy</h2><p>Escaneá el QR del profesor para registrar tu presencia</p></div>
      </div>

      <?php if (empty($clases_hoy)): ?>
        <div class="card" style="padding:32px;text-align:center;color:var(--c-text-faint)">
          <i class="fa-solid fa-calendar-xmark" style="font-size:2rem;margin-bottom:12px"></i>
          <p>No tenés materias asignadas o no hay clases programadas para hoy.</p>
        </div>
      <?php else: ?>
      <div class="class-list">
        <?php foreach ($clases_hoy as $c):
          $tiene_clase  = !empty($c['id']);
          $estado_clase = $c['estado'] ?? 'pendiente';
          $css_class    = $estado_class[$estado_clase] ?? 'state-pendiente';
          $ya_escaneo   = !empty($c['mi_estado']);
        ?>
        <article class="class-card <?= $tiene_clase ? $css_class : 'state-pendiente' ?>">
          <div class="class-time">
            <div class="hh"><?= $tiene_clase ? substr($c['hora_inicio'],0,5) : '—' ?></div>
            <div class="dur"><?= $tiene_clase ? $c['duracion_min'].' min' : '' ?></div>
          </div>
          <div class="class-info">
            <h3><?= htmlspecialchars($c['materia']) ?></h3>
            <div class="meta">
              <span><i class="fa-solid fa-chalkboard-user"></i> Prof. <?= htmlspecialchars($c['profesor']) ?></span>
              <?php if ($tiene_clase): ?>
                <?php if ($c['modalidad'] === 'virtual'): ?>
                  <span><i class="fa-solid fa-video"></i> Virtual</span>
                <?php else: ?>
                  <span><i class="fa-solid fa-location-dot"></i> Aula <?= htmlspecialchars($c['aula'] ?? '—') ?></span>
                <?php endif; ?>

                <?php if ($ya_escaneo):
                  $b = $badge_est[$c['mi_estado']] ?? ['badge-muted','—'];
                  $hora_txt = $c['entrada'] ? 'entrada '.$c['entrada'] : '';
                  if ($c['salida']) $hora_txt .= ($hora_txt ? ' · ' : '').'salida '.$c['salida'];
                ?>
                  <span class="badge <?= $b[0] ?>"><?= $b[1] ?><?= $hora_txt ? ' · '.$hora_txt : '' ?></span>
                <?php elseif ($estado_clase === 'en_curso'): ?>
                  <span class="badge badge-warning">En curso · esperando tu escaneo</span>
                <?php elseif ($estado_clase === 'finalizada'): ?>
                  <span class="badge badge-danger">Finalizada · no registrado</span>
                <?php else: ?>
                  <span class="badge badge-muted">Pendiente</span>
                <?php endif; ?>
              <?php else: ?>
                <span class="badge badge-muted">Sin clase programada hoy</span>
              <?php endif; ?>
            </div>
          </div>
          <div class="class-actions">
            <?php if ($ya_escaneo): ?>
              <button class="btn btn-ghost btn-sm" disabled><i class="fa-solid fa-check"></i> Registrada</button>
            <?php elseif ($tiene_clase && $estado_clase === 'en_curso'): ?>
              <a href="escanear.php" class="btn btn-accent btn-sm"><i class="fa-solid fa-qrcode"></i> Registrar presencia</a>
            <?php else: ?>
              <button class="btn btn-ghost btn-sm" disabled><i class="fa-regular fa-clock"></i>
                <?= $estado_clase === 'finalizada' ? 'Ya finalizó' : 'Aún no empezó' ?>
              </button>
            <?php endif; ?>
          </div>
        </article>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>

      <!-- Historial reciente -->
      <div class="page-head mt-3" id="historial">
        <div><h2>Mi asistencia reciente</h2><p>Últimos registros</p></div>
      </div>
      <div class="card table-card">
        <div class="table-scroll">
          <table class="data-table">
            <thead><tr><th>Materia</th><th>Profesor</th><th>Fecha</th><th>Entrada</th><th>Salida</th><th>Estado</th></tr></thead>
            <tbody>
            <?php foreach ($recientes as $r):
              $b = $badge_est[$r['estado']] ?? ['badge-muted', ucfirst($r['estado'])];
            ?>
              <tr>
                <td><?= htmlspecialchars($r['materia']) ?></td>
                <td><?= htmlspecialchars($r['profesor']) ?></td>
                <td><?= date('d/m/Y', strtotime($r['fecha'])) ?></td>
                <td><?= htmlspecialchars($r['entrada']) ?></td>
                <td><?= htmlspecialchars($r['salida']) ?></td>
                <td><span class="badge <?= $b[0] ?>"><?= $b[1] ?></span></td>
              </tr>
            <?php endforeach; ?>
            <?php if (empty($recientes)): ?>
              <tr><td colspan="6" style="text-align:center;padding:24px;color:var(--c-text-faint)">
                Sin registros de asistencia todavía.
              </td></tr>
            <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </main>
  </div>
</div>
<script src="../assets/js/utils.js"></script>
</body>
</html>

