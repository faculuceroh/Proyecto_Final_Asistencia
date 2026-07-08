<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" /><meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?= htmlspecialchars($materia['nombre']) ?> · Asistencia QR</title>
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="../assets/vendor/fontawesome/css/all.min.css" />
  <link rel="stylesheet" href="../assets/css/main.css" />
  <link rel="stylesheet" href="../assets/css/dashboard.css" />
</head>
<body>
<div class="app-layout role-alumno">
  <aside class="sidebar">
    <div class="sidebar-brand">
      <img src="../assets/img/logo-dashboard.png" alt="Logo" />
      <div><div class="name">Asistencia QR</div><div class="sub">Portal Alumno</div></div>
    </div>
    <nav class="sidebar-nav">
      <span class="nav-label">Principal</span>
      <a href="dashboard.php"><i class="fa-solid fa-house"></i> Inicio</a>
      <a href="escanear.php"><i class="fa-solid fa-qrcode"></i> Escanear QR</a>
      <a href="materias.php" class="active"><i class="fa-solid fa-book"></i> Mis materias</a>
      <span class="nav-label">Cuenta</span>
      <a href="perfil.php"><i class="fa-solid fa-user"></i> Mi perfil</a>
      <a href="../logout.php"><i class="fa-solid fa-right-from-bracket"></i> Cerrar sesión</a>
    </nav>
    <div class="sidebar-user">
      <?php if (!empty($_SESSION['foto'])): ?><img class="avatar" src="../assets/uploads/perfiles/<?= htmlspecialchars($_SESSION['foto']) ?>" alt="Foto de perfil" /><?php else: ?><div class="avatar"><?= htmlspecialchars($iniciales) ?></div><?php endif; ?>
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
      <div class="page-title">
        <?= htmlspecialchars($materia['nombre']) ?>
        <small><?= htmlspecialchars($materia['curso']) ?></small>
      </div>
      <div class="topbar-right">
        <a href="materias.php" class="btn btn-ghost btn-sm">
          <i class="fa-solid fa-arrow-left"></i> Mis materias
        </a>
      </div>
    </header>

    <main class="app-content">

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

    </main>
  </div>
</div>
<script src="../assets/js/utils.js"></script>
</body>
</html>
