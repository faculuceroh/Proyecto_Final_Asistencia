<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" /><meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Reportes · Secretaría</title>
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
      <a href="materias.php"><i class="fa-solid fa-book"></i> Materias</a>
      <a href="aulas.php"><i class="fa-solid fa-door-open"></i> Aulas</a>
      <a href="inscripciones.php"><i class="fa-solid fa-user-plus"></i> Inscripciones</a>
      <a href="usuarios.php"><i class="fa-solid fa-users"></i> Alta de usuarios</a>
      <a href="reportes.php" class="active"><i class="fa-solid fa-chart-pie"></i> Reportes</a>
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
      <div class="page-title">Reportes <small>Estadísticas de asistencia del período</small></div>
    </header>
    <main class="app-content">

      <!-- Stats -->
      <div class="stat-grid">
        <div class="stat-card"><div class="stat-icon i-green"><i class="fa-solid fa-percent"></i></div><div><div class="stat-value"><?= $prom_asist ?>%</div><div class="stat-label">Asistencia promedio</div></div></div>
        <div class="stat-card"><div class="stat-icon i-blue"><i class="fa-solid fa-user-check"></i></div><div><div class="stat-value"><?= number_format($total_pres) ?></div><div class="stat-label">Presentes</div></div></div>
        <div class="stat-card"><div class="stat-icon i-amber"><i class="fa-solid fa-user-xmark"></i></div><div><div class="stat-value"><?= number_format($total_aus) ?></div><div class="stat-label">Ausentes</div></div></div>
        <div class="stat-card"><div class="stat-icon i-navy"><i class="fa-solid fa-triangle-exclamation"></i></div><div><div class="stat-value"><?= $en_riesgo ?></div><div class="stat-label">Alumnos en riesgo</div></div></div>
      </div>

      <div style="display:grid;grid-template-columns:1.3fr 1fr;gap:20px;align-items:start">

        <!-- Asistencia por materia -->
        <div class="card" style="padding:22px">
          <h3 style="font-size:1.05rem;margin-bottom:18px">Asistencia por materia</h3>
          <?php if (empty($por_materia)): ?>
            <p class="text-muted" style="font-size:0.9rem">Sin datos todavía. Las estadísticas aparecen cuando hay clases finalizadas.</p>
          <?php endif; ?>
          <div style="display:flex;flex-direction:column;gap:16px">
          <?php foreach ($por_materia as $m):
            $pct = (float)($m['pct'] ?? 0);
            $color = $pct >= 75 ? 'var(--c-success)' : ($pct >= 50 ? 'var(--c-warning)' : 'var(--c-danger)');
          ?>
            <div>
              <div style="display:flex;justify-content:space-between;font-size:0.86rem;margin-bottom:6px">
                <span><?= htmlspecialchars($m['materia']) ?></span>
                <strong><?= $pct ?>%</strong>
              </div>
              <div class="progress" style="height:10px">
                <span style="width:<?= $pct ?>%;background:<?= $color ?>"></span>
              </div>
            </div>
          <?php endforeach; ?>
          </div>
        </div>

        <!-- Alumnos en riesgo -->
        <div class="card table-card">
          <div class="toolbar" style="padding:16px 16px 0;margin:0">
            <h3 style="font-size:1.05rem">Alumnos en riesgo</h3>
            <div class="spacer"></div>
            <button class="btn btn-success btn-sm"
                    data-export-url="../api/exportar_riesgo.php">
              <i class="fa-solid fa-file-excel"></i> Exportar
            </button>
          </div>
          <div class="table-scroll">
            <table class="data-table">
              <thead><tr><th>Alumno</th><th>Curso</th><th>Asistencia</th></tr></thead>
              <tbody>
              <?php foreach ($riesgo as $r):
                $pct = (float)($r['porcentaje'] ?? 0);
                $b   = $pct < 60 ? 'badge-danger' : 'badge-warning';
              ?>
                <tr>
                  <td>
                    <div class="cell-name">
                      <span class="mini-avatar">
                        <?= strtoupper(substr($r['nombre'],0,1).substr($r['apellido'],0,1)) ?>
                      </span>
                      <?= htmlspecialchars($r['nombre'].' '.$r['apellido']) ?>
                    </div>
                  </td>
                  <td><?= htmlspecialchars($r['curso'] ?? '—') ?></td>
                  <td><span class="badge <?= $b ?>"><?= $pct ?>%</span></td>
                </tr>
              <?php endforeach; ?>
              <?php if (empty($riesgo)): ?>
                <tr><td colspan="3" style="text-align:center;padding:24px;color:var(--c-text-faint)">
                  Sin alumnos en riesgo.
                </td></tr>
              <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>

      </div>
    </main>
  </div>
</div>
<script src="../assets/js/utils.js"></script>
<script src="../assets/js/export.js"></script>
</body>
</html>
