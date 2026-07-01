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
      <a href="escanear.php"><i class="fa-solid fa-qrcode"></i> Escanear QR</a>
      <a href="historial.php"><i class="fa-solid fa-clock-rotate-left"></i> Mi asistencia</a>
      <span class="nav-label">Cuenta</span>
      <a href="perfil.php"><i class="fa-solid fa-user"></i> Mi perfil</a>
      <a href="../logout.php"><i class="fa-solid fa-right-from-bracket"></i> Cerrar sesión</a>
    </nav>
    <div class="sidebar-user">
      <?php if (!empty($_SESSION['foto'])): ?><img class="avatar" src="../assets/uploads/perfiles/<?= htmlspecialchars($_SESSION['foto']) ?>" alt="Foto de perfil" /><?php else: ?><div class="avatar"><?= htmlspecialchars($iniciales) ?></div><?php endif; ?>
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

      <!-- Mis materias -->
      <div style="margin-bottom:32px">
        <h2 style="font-size:1.1rem;margin-bottom:4px">Mis materias</h2>
        <p class="text-muted" style="font-size:0.88rem;margin-bottom:16px">Materias en las que estás inscripto y su horario semanal</p>

        <?php if (empty($mis_materias)): ?>
          <div class="empty-state">
            <i class="fa-solid fa-book-open"></i>
            <p>No tenés materias asignadas todavía.</p>
          </div>
        <?php else: ?>
        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:16px">
          <?php foreach ($mis_materias as $mat): ?>
          <div class="card" style="padding:20px">
            <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:8px;margin-bottom:12px">
              <div>
                <div style="font-weight:700;font-size:1rem"><?= htmlspecialchars($mat['nombre']) ?></div>
                <div class="text-muted" style="font-size:0.83rem"><?= htmlspecialchars($mat['curso']) ?></div>
              </div>
              <span class="badge <?= $mat['modalidad']==='virtual'?'badge-muted':'badge-accent' ?>">
                <?= ucfirst($mat['modalidad']) ?>
              </span>
            </div>

            <div style="font-size:0.83rem;color:var(--c-text-soft);margin-bottom:10px">
              <i class="fa-solid fa-chalkboard-user"></i> Prof. <?= htmlspecialchars($mat['profesor']) ?>
            </div>

            <?php if (!empty($mat['horarios'])): ?>
            <div style="display:flex;flex-direction:column;gap:6px;margin-bottom:12px">
              <?php foreach ($mat['horarios'] as $h): ?>
              <div style="display:flex;align-items:center;gap:8px;font-size:0.85rem">
                <span style="background:var(--c-primary);color:#fff;border-radius:6px;
                             padding:2px 8px;font-weight:700;font-size:0.75rem;flex-shrink:0">
                  <?= $nombres_dia[$h['dia_semana']] ?>
                </span>
                <span class="text-muted"><?= substr($h['hora_inicio'],0,5) ?> — <?= substr($h['hora_fin'],0,5) ?></span>
              </div>
              <?php endforeach; ?>
            </div>
            <?php else: ?>
            <p class="text-muted" style="font-size:0.83rem;margin-bottom:12px">Sin horario configurado</p>
            <?php endif; ?>

            <?php if ($mat['pct'] !== null): ?>
            <div style="border-top:1px solid var(--c-border);padding-top:10px;font-size:0.82rem;display:flex;align-items:center;gap:8px">
              <div class="progress" style="flex:1">
                <span style="width:<?= $mat['pct'] ?>%;background:<?= $mat['pct']>=75?'var(--c-success)':($mat['pct']>=50?'var(--c-warning)':'var(--c-danger)') ?>"></span>
              </div>
              <span style="color:var(--c-text-soft);white-space:nowrap"><?= $mat['pct'] ?>% asistencia</span>
            </div>
            <?php else: ?>
            <div style="border-top:1px solid var(--c-border);padding-top:10px;font-size:0.82rem;color:var(--c-text-faint)">
              Sin clases finalizadas aún
            </div>
            <?php endif; ?>
          </div>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
      </div>

      <!-- Clases de hoy -->
      <div class="page-head">
        <div><h2>Clases de hoy</h2><p>Escaneá el QR del profesor para registrar tu presencia</p></div>
      </div>

      <?php if (empty($clases_hoy)): ?>
        <div class="card" style="padding:32px;text-align:center;color:var(--c-text-faint);margin-bottom:32px">
          <i class="fa-solid fa-calendar-xmark" style="font-size:2rem;margin-bottom:12px"></i>
          <p>No hay clases programadas para hoy.</p>
        </div>
      <?php else: ?>
      <div class="class-list">
        <?php foreach ($clases_hoy as $c):
          $estado_clase = $c['estado'] ?? 'pendiente';
          $css_class    = $estado_class[$estado_clase] ?? 'state-pendiente';
          $ya_escaneo   = !empty($c['mi_estado']);
        ?>
        <article class="class-card <?= $css_class ?>">
          <div class="class-time">
            <div class="hh"><?= substr($c['hora_inicio'],0,5) ?></div>
            <div class="dur"><?= $c['duracion_min'] ?> min</div>
          </div>
          <div class="class-info">
            <h3><?= htmlspecialchars($c['materia']) ?></h3>
            <div class="meta">
              <span><i class="fa-solid fa-chalkboard-user"></i> Prof. <?= htmlspecialchars($c['profesor']) ?></span>
              <?php if ($c['modalidad'] === 'virtual'): ?>
                <span><i class="fa-solid fa-video"></i> Virtual</span>
              <?php elseif ($c['aula']): ?>
                <span><i class="fa-solid fa-location-dot"></i> Aula <?= htmlspecialchars($c['aula']) ?></span>
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
            </div>
          </div>
          <div class="class-actions">
            <?php if ($ya_escaneo): ?>
              <button class="btn btn-ghost btn-sm" disabled><i class="fa-solid fa-check"></i> Registrada</button>
            <?php elseif ($estado_clase === 'en_curso'): ?>
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
