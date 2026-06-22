<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';
require_auth(['alumno']);

$pdo    = getPDO();
$alu_id = $_SESSION['usuario_id'];

// Materias inscriptas con % de asistencia
$stmt = $pdo->prepare(
    "SELECT m.id, m.nombre, m.curso, m.modalidad,
            COALESCE(CONCAT(u.nombre,' ',u.apellido),'—') AS profesor,
            ROUND(
              SUM(a.estado IN ('presente','tardanza'))
              / NULLIF(COUNT(DISTINCT c.id), 0) * 100, 1
            ) AS pct,
            COUNT(DISTINCT c.id) AS total_clases
     FROM inscripciones i
     JOIN materias m ON m.id = i.materia_id
     LEFT JOIN usuarios u ON u.id = m.profesor_id
     LEFT JOIN clases c ON c.materia_id = m.id AND c.estado = 'finalizada'
     LEFT JOIN asistencias a ON a.clase_id = c.id AND a.alumno_id = i.alumno_id
     WHERE i.alumno_id = ?
     GROUP BY m.id
     ORDER BY m.nombre"
);
$stmt->execute([$alu_id]);
$materias = $stmt->fetchAll();

// Stats resumen
$stmt = $pdo->prepare(
    "SELECT ROUND(SUM(a.estado IN ('presente','tardanza'))/NULLIF(COUNT(*),0)*100,1)
     FROM asistencias a
     JOIN clases c ON c.id = a.clase_id
     JOIN inscripciones i ON i.materia_id = c.materia_id AND i.alumno_id = a.alumno_id
     WHERE a.alumno_id = ?"
);
$stmt->execute([$alu_id]);
$pct_global = $stmt->fetchColumn() ?? 0;

$partes    = explode(' ', $_SESSION['nombre']);
$iniciales = strtoupper(substr($partes[0],0,1).substr($partes[1]??'',0,1));

$stmt = $pdo->prepare('SELECT curso FROM usuarios WHERE id = ? LIMIT 1');
$stmt->execute([$alu_id]);
$curso = $stmt->fetchColumn() ?? '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" /><meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Mis materias · Asistencia QR</title>
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
      <a href="dashboard.php"><i class="fa-solid fa-house"></i> Inicio</a>
      <a href="materias.php" class="active"><i class="fa-solid fa-book"></i> Mis materias</a>
      <a href="escanear.php"><i class="fa-solid fa-qrcode"></i> Escanear QR</a>
      <span class="nav-label">Cuenta</span>
      <a href="perfil.php"><i class="fa-solid fa-user"></i> Mi perfil</a>
      <a href="../logout.php"><i class="fa-solid fa-right-from-bracket"></i> Cerrar sesión</a>
    </nav>
    <div class="sidebar-user">
      <div class="avatar"><?= htmlspecialchars($iniciales) ?></div>
      <div class="meta">
        <div class="u-name"><?= htmlspecialchars($_SESSION['nombre']) ?></div>
        <div class="u-role"><?= $curso ? 'Alumno · '.$curso : 'Alumno' ?></div>
      </div>
      <a href="../logout.php" class="logout"><i class="fa-solid fa-right-from-bracket"></i></a>
    </div>
  </aside>
  <div class="sidebar-backdrop" data-sidebar-backdrop></div>

  <div class="app-main">
    <header class="topbar">
      <button class="hamburger" data-sidebar-toggle aria-label="Menú"><i class="fa-solid fa-bars"></i></button>
      <div class="page-title">Mis materias <small>Materias en las que estás anotado este período</small></div>
    </header>

    <main class="app-content">
      <div class="page-head">
        <div>
          <h2><?= $curso ? $curso.' · ' : '' ?>Período actual</h2>
          <p><?= count($materias) ?> materia<?= count($materias) !== 1 ? 's' : '' ?> · asistencia general <?= $pct_global ?>%</p>
        </div>
      </div>

      <?php if (empty($materias)): ?>
        <div class="card" style="padding:32px;text-align:center;color:var(--c-text-faint)">
          <i class="fa-solid fa-book-open" style="font-size:2rem;margin-bottom:12px"></i>
          <p>Todavía no tenés materias asignadas. Contactá a secretaría.</p>
        </div>
      <?php else: ?>
      <div class="subject-grid">
        <?php foreach ($materias as $m):
          $pct   = (float)($m['pct'] ?? 0);
          $color = $pct >= 75 ? 'var(--c-success)' : ($pct >= 50 ? 'var(--c-warning)' : 'var(--c-danger)');
          $es_virtual = $m['modalidad'] === 'virtual';
        ?>
        <article class="card subject-card <?= $es_virtual ? 'virtual' : '' ?>">
          <div class="subject-head">
            <h3><?= htmlspecialchars($m['nombre']) ?></h3>
            <span class="badge <?= $es_virtual ? 'badge-muted' : 'badge-accent' ?>">
              <?= ucfirst($m['modalidad']) ?>
            </span>
          </div>
          <div class="subject-meta">
            <span><i class="fa-solid fa-chalkboard-user"></i> Prof. <?= htmlspecialchars($m['profesor']) ?></span>
            <span><i class="fa-solid fa-users"></i> <?= $m['total_clases'] ?> clase<?= $m['total_clases'] != 1 ? 's' : '' ?> dictada<?= $m['total_clases'] != 1 ? 's' : '' ?></span>
            <?php if ($es_virtual): ?>
              <span><i class="fa-solid fa-video"></i> Virtual</span>
            <?php endif; ?>
          </div>
          <div class="subject-att">
            <span><?= $m['total_clases'] > 0 ? $pct.'%' : '—' ?></span>
            <div class="progress">
              <span style="width:<?= $pct ?>%;background:<?= $color ?>"></span>
            </div>
          </div>
          <?php if ($pct > 0 && $pct < 75): ?>
            <div style="font-size:0.78rem;color:var(--c-danger);margin:-4px 0 8px;font-weight:600">
              <i class="fa-solid fa-triangle-exclamation"></i> En riesgo de quedar libre (mínimo 75%)
            </div>
          <?php endif; ?>
          <div class="subject-foot">
            <a href="dashboard.php#historial" class="btn btn-ghost btn-sm">
              <i class="fa-solid fa-clock-rotate-left"></i> Ver asistencia
            </a>
          </div>
        </article>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </main>
  </div>
</div>
<script src="../assets/js/utils.js"></script>
</body>
</html>
