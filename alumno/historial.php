<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';
require_auth(['alumno']);

$pdo       = getPDO();
$alu_id    = $_SESSION['usuario_id'];
$materia_id = (int)($_GET['materia_id'] ?? 0) ?: null;

$partes    = explode(' ', $_SESSION['nombre']);
$iniciales = strtoupper(substr($partes[0],0,1).substr($partes[1]??'',0,1));

$badge_asist = ['presente'=>'badge-success','tardanza'=>'badge-warning','ausente'=>'badge-danger','sin_registro'=>'badge-muted'];
$label_asist = ['presente'=>'Presente','tardanza'=>'Tardanza','ausente'=>'Ausente','sin_registro'=>'Sin registro'];

// ── Vista detalle de una materia ──────────────────────────────
if ($materia_id) {

    // Verificar que el alumno esté inscripto
    $stmt = $pdo->prepare(
        "SELECT COUNT(*) FROM inscripciones WHERE alumno_id = ? AND materia_id = ?"
    );
    $stmt->execute([$alu_id, $materia_id]);
    if (!$stmt->fetchColumn()) { header('Location: historial.php'); exit; }

    // Info de la materia
    $stmt = $pdo->prepare(
        "SELECT m.id, m.nombre, m.curso, m.modalidad,
                COALESCE(CONCAT(u.nombre,' ',u.apellido),'—') AS profesor
         FROM materias m
         LEFT JOIN usuarios u ON u.id = m.profesor_id
         WHERE m.id = ? AND m.activo = 1 LIMIT 1"
    );
    $stmt->execute([$materia_id]);
    $materia = $stmt->fetch();
    if (!$materia) { header('Location: historial.php'); exit; }

    // Clases de la materia hasta hoy, con asistencia del alumno
    $stmt = $pdo->prepare(
        "SELECT c.id, c.fecha, c.hora_inicio, c.duracion_min, c.estado AS estado_clase,
                COALESCE(a.estado, 'sin_registro') AS estado_asist,
                TIME_FORMAT(a.hora_entrada, '%H:%i') AS hora_entrada
         FROM clases c
         LEFT JOIN asistencias a ON a.clase_id = c.id AND a.alumno_id = ?
         WHERE c.materia_id = ? AND c.fecha <= CURDATE()
         ORDER BY c.fecha DESC"
    );
    $stmt->execute([$alu_id, $materia_id]);
    $clases = $stmt->fetchAll();

    // Stats
    $finalizadas = array_filter($clases, fn($c) => $c['estado_clase'] === 'finalizada');
    $total_fin   = count($finalizadas);
    $presentes   = count(array_filter($finalizadas, fn($c) => in_array($c['estado_asist'], ['presente','tardanza'])));
    $ausentes    = count(array_filter($finalizadas, fn($c) => $c['estado_asist'] === 'ausente'));
    $pct         = $total_fin ? round($presentes / $total_fin * 100, 1) : 0;
}

// ── Vista tarjetas de materias ────────────────────────────────
else {
    $stmt = $pdo->prepare(
        "SELECT m.id, m.nombre, m.curso, m.modalidad,
                COALESCE(CONCAT(u.nombre,' ',u.apellido),'—') AS profesor,
                COUNT(DISTINCT c.id) AS total_clases,
                COALESCE(SUM(a.estado IN ('presente','tardanza')), 0) AS presentes,
                COALESCE(SUM(a.estado = 'ausente'), 0) AS ausentes,
                ROUND(SUM(a.estado IN ('presente','tardanza'))/NULLIF(COUNT(DISTINCT c.id),0)*100,1) AS pct
         FROM inscripciones i
         JOIN materias m ON m.id = i.materia_id AND m.activo = 1
         LEFT JOIN usuarios u ON u.id = m.profesor_id
         LEFT JOIN clases c ON c.materia_id = m.id AND c.estado = 'finalizada'
         LEFT JOIN asistencias a ON a.clase_id = c.id AND a.alumno_id = i.alumno_id
         WHERE i.alumno_id = ?
         GROUP BY m.id
         ORDER BY m.nombre"
    );
    $stmt->execute([$alu_id]);
    $mis_materias = $stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" /><meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Mi asistencia · Asistencia QR</title>
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
      <a href="escanear.php"><i class="fa-solid fa-qrcode"></i> Escanear QR</a>
      <a href="historial.php" class="active"><i class="fa-solid fa-clock-rotate-left"></i> Mi asistencia</a>
      <span class="nav-label">Cuenta</span>
      <a href="perfil.php"><i class="fa-solid fa-user"></i> Mi perfil</a>
      <a href="../logout.php"><i class="fa-solid fa-right-from-bracket"></i> Cerrar sesión</a>
    </nav>
    <div class="sidebar-user">
      <div class="avatar"><?= htmlspecialchars($iniciales) ?></div>
      <div class="meta">
        <div class="u-name"><?= htmlspecialchars($_SESSION['nombre']) ?></div>
        <div class="u-role">Alumno</div>
      </div>
      <a href="../logout.php" class="logout"><i class="fa-solid fa-right-from-bracket"></i></a>
    </div>
  </aside>
  <div class="sidebar-backdrop" data-sidebar-backdrop></div>

  <div class="app-main">
    <header class="topbar">
      <button class="hamburger" data-sidebar-toggle aria-label="Menú"><i class="fa-solid fa-bars"></i></button>
      <?php if ($materia_id): ?>
        <div class="page-title">
          <?= htmlspecialchars($materia['nombre']) ?>
          <small><?= htmlspecialchars($materia['curso']) ?></small>
        </div>
        <div class="topbar-right">
          <a href="historial.php" class="btn btn-ghost btn-sm">
            <i class="fa-solid fa-arrow-left"></i> Mis materias
          </a>
        </div>
      <?php else: ?>
        <div class="page-title">Mi asistencia <small>Seleccioná una materia para ver el detalle</small></div>
      <?php endif; ?>
    </header>

    <main class="app-content">

    <?php if ($materia_id): ?>
      <!-- ── VISTA DETALLE DE MATERIA ──────────────────────── -->

      <div style="font-size:0.88rem;color:var(--c-text-soft);margin-bottom:20px">
        <i class="fa-solid fa-chalkboard-user"></i> Prof. <?= htmlspecialchars($materia['profesor']) ?>
      </div>

      <div class="stat-grid" style="margin-bottom:24px">
        <div class="stat-card">
          <div class="stat-icon i-blue"><i class="fa-solid fa-calendar-check"></i></div>
          <div><div class="stat-value"><?= $total_fin ?></div><div class="stat-label">Clases tomadas</div></div>
        </div>
        <div class="stat-card">
          <div class="stat-icon i-green"><i class="fa-solid fa-user-check"></i></div>
          <div><div class="stat-value"><?= $presentes ?></div><div class="stat-label">Presentes</div></div>
        </div>
        <div class="stat-card">
          <div class="stat-icon i-amber"><i class="fa-solid fa-user-xmark"></i></div>
          <div><div class="stat-value"><?= $ausentes ?></div><div class="stat-label">Ausentes</div></div>
        </div>
        <div class="stat-card">
          <div class="stat-icon i-<?= $pct>=75?'green':($pct>=50?'amber':'red') ?>">
            <i class="fa-solid fa-percent"></i>
          </div>
          <div><div class="stat-value"><?= $pct ?>%</div><div class="stat-label">Asistencia</div></div>
        </div>
      </div>

      <?php if (empty($clases)): ?>
        <div class="card" style="padding:32px;text-align:center;color:var(--c-text-faint)">
          <i class="fa-solid fa-calendar-xmark" style="font-size:2rem;margin-bottom:12px"></i>
          <p>No hay clases registradas para esta materia todavía.</p>
        </div>
      <?php else: ?>
      <div class="card table-card">
        <div class="table-scroll">
          <table class="data-table">
            <thead>
              <tr><th>Fecha</th><th>Hora</th><th>Duración</th><th>Hora entrada</th><th>Estado</th></tr>
            </thead>
            <tbody>
            <?php foreach ($clases as $c):
              $fin = $c['estado_clase'] === 'finalizada';
              $estado = $fin ? $c['estado_asist'] : 'pendiente';
            ?>
              <tr>
                <td><?= date('d/m/Y', strtotime($c['fecha'])) ?></td>
                <td><?= substr($c['hora_inicio'], 0, 5) ?></td>
                <td><?= $c['duracion_min'] ?> min</td>
                <td><?= $fin ? ($c['hora_entrada'] ?? '—') : '—' ?></td>
                <td>
                  <?php if (!$fin): ?>
                    <span class="badge badge-muted">
                      <?= $c['estado_clase'] === 'en_curso' ? 'En curso' : 'Pendiente' ?>
                    </span>
                  <?php else: ?>
                    <span class="badge <?= $badge_asist[$estado] ?? 'badge-muted' ?>">
                      <?= $label_asist[$estado] ?? ucfirst($estado) ?>
                    </span>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
      <?php endif; ?>

    <?php else: ?>
      <!-- ── VISTA TARJETAS DE MATERIAS ────────────────────── -->

      <?php if (empty($mis_materias)): ?>
        <div class="empty-state">
          <i class="fa-solid fa-book-open"></i>
          <p>No tenés materias asignadas todavía.</p>
        </div>
      <?php else: ?>
      <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:16px;align-items:stretch">
        <?php foreach ($mis_materias as $mat):
          $pct_mat = (float)($mat['pct'] ?? 0);
          $color   = $pct_mat >= 75 ? 'var(--c-success)' : ($pct_mat >= 50 ? 'var(--c-warning)' : 'var(--c-danger)');
        ?>
        <a href="historial.php?materia_id=<?= $mat['id'] ?>"
           style="text-decoration:none;color:inherit;display:flex">
          <div class="card" style="padding:20px;cursor:pointer;transition:box-shadow .15s;
                                   display:flex;flex-direction:column;width:100%"
               onmouseenter="this.style.boxShadow='0 4px 16px rgba(0,0,0,.1)'"
               onmouseleave="this.style.boxShadow=''">

            <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:8px;margin-bottom:10px">
              <div>
                <div style="font-weight:700;font-size:1rem"><?= htmlspecialchars($mat['nombre']) ?></div>
                <div class="text-muted" style="font-size:0.83rem"><?= htmlspecialchars($mat['curso']) ?></div>
              </div>
              <span class="badge <?= $mat['modalidad']==='virtual'?'badge-muted':'badge-accent' ?>">
                <?= ucfirst($mat['modalidad']) ?>
              </span>
            </div>

            <div style="font-size:0.83rem;color:var(--c-text-soft);margin-bottom:14px">
              <i class="fa-solid fa-chalkboard-user"></i> Prof. <?= htmlspecialchars($mat['profesor']) ?>
            </div>

            <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:8px;margin-bottom:14px;text-align:center">
              <div style="background:var(--c-bg-soft);border-radius:8px;padding:8px 4px">
                <div style="font-weight:700;font-size:1.1rem"><?= (int)$mat['total_clases'] ?></div>
                <div style="font-size:0.72rem;color:var(--c-text-faint)">Clases</div>
              </div>
              <div style="background:var(--c-bg-soft);border-radius:8px;padding:8px 4px">
                <div style="font-weight:700;font-size:1.1rem;color:var(--c-success)"><?= (int)$mat['presentes'] ?></div>
                <div style="font-size:0.72rem;color:var(--c-text-faint)">Presentes</div>
              </div>
              <div style="background:var(--c-bg-soft);border-radius:8px;padding:8px 4px">
                <div style="font-weight:700;font-size:1.1rem;color:var(--c-danger)"><?= (int)$mat['ausentes'] ?></div>
                <div style="font-size:0.72rem;color:var(--c-text-faint)">Ausentes</div>
              </div>
            </div>

            <div style="min-height:24px">
            <?php if ($mat['total_clases'] > 0): ?>
              <div style="display:flex;align-items:center;gap:8px;font-size:0.83rem">
                <div class="progress" style="flex:1">
                  <span style="width:<?= $pct_mat ?>%;background:<?= $color ?>"></span>
                </div>
                <span style="font-weight:700;color:<?= $color ?>;white-space:nowrap"><?= $pct_mat ?>%</span>
              </div>
            <?php else: ?>
              <div style="font-size:0.82rem;color:var(--c-text-faint)">Sin clases finalizadas aún</div>
            <?php endif; ?>
            </div>

            <div style="margin-top:auto;padding-top:12px;text-align:right;font-size:0.82rem;color:var(--c-primary);font-weight:600">
              Ver detalle <i class="fa-solid fa-chevron-right" style="font-size:0.7rem"></i>
            </div>
          </div>
        </a>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>

    <?php endif; ?>

    </main>
  </div>
</div>
<script src="../assets/js/utils.js"></script>
</body>
</html>
