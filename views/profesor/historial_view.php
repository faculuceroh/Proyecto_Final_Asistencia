<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" /><meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Historial · Asistencia QR</title>
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
      <a href="historial.php" class="active"><i class="fa-solid fa-clock-rotate-left"></i> Historial</a>
      <span class="nav-label">Cuenta</span>
      <a href="perfil.php"><i class="fa-solid fa-user"></i> Mi perfil</a>
      <a href="../logout.php"><i class="fa-solid fa-right-from-bracket"></i> Cerrar sesión</a>
    </nav>
    <div class="sidebar-user">
      <?php if (!empty($_SESSION['foto'])): ?><img class="avatar" src="../assets/uploads/perfiles/<?= htmlspecialchars($_SESSION['foto']) ?>" alt="Foto de perfil" /><?php else: ?><div class="avatar"><?= htmlspecialchars($iniciales) ?></div><?php endif; ?>
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
      <?php if ($materia_id && $clase_id): ?>
        <div class="page-title">
          <?= htmlspecialchars($materia['nombre']) ?>
          <small><?= date('d/m/Y', strtotime($clase['fecha'])) ?> · <?= substr($clase['hora_inicio'],0,5) ?></small>
        </div>
        <div class="topbar-right">
          <a href="historial.php?materia_id=<?= $materia_id ?>" class="btn btn-ghost btn-sm">
            <i class="fa-solid fa-arrow-left"></i> Clases
          </a>
          <button class="btn btn-success btn-sm"
                  data-export-url="../api/exportar.php?clase_id=<?= $clase_id ?>">
            <i class="fa-solid fa-file-excel"></i> Exportar
          </button>
        </div>
      <?php elseif ($materia_id): ?>
        <div class="page-title">
          <?= htmlspecialchars($materia['nombre']) ?>
          <small><?= htmlspecialchars($materia['curso']) ?></small>
        </div>
        <div class="topbar-right">
          <a href="historial.php" class="btn btn-ghost btn-sm">
            <i class="fa-solid fa-arrow-left"></i> Mis materias
          </a>
          <button class="btn btn-success btn-sm"
                  data-export-url="../api/exportar.php?materia_id=<?= $materia_id ?>">
            <i class="fa-solid fa-file-excel"></i> Exportar
          </button>
        </div>
      <?php else: ?>
        <div class="page-title">Historial <small>Seleccioná una materia</small></div>
      <?php endif; ?>
    </header>

    <main class="app-content">

    <?php if ($materia_id && $clase_id): ?>
      <!-- ── VISTA ALUMNOS DE UNA CLASE ────────────────────── -->

      <!-- Stats de la clase -->
      <div class="stat-grid" style="margin-bottom:24px">
        <div class="stat-card">
          <div class="stat-icon i-navy"><i class="fa-solid fa-users"></i></div>
          <div><div class="stat-value"><?= $total_al ?></div><div class="stat-label">Inscriptos</div></div>
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
          <div class="stat-icon i-blue"><i class="fa-solid fa-percent"></i></div>
          <div><div class="stat-value"><?= $pct_clase ?>%</div><div class="stat-label">Asistencia</div></div>
        </div>
      </div>

      <?php if ($clase['estado'] === 'pendiente' && empty($alumnos)): ?>
        <div class="card" style="padding:24px;text-align:center;color:var(--c-text-faint)">
          <i class="fa-solid fa-clock" style="font-size:2rem;margin-bottom:10px"></i>
          <p>Esta clase aún no fue tomada. La asistencia estará disponible una vez finalizada.</p>
        </div>
      <?php else: ?>
      <div class="card table-card">
        <div class="table-scroll">
          <table class="data-table">
            <thead>
              <tr><th>Alumno</th><th>Legajo</th><th>Hora entrada</th><th>Estado</th></tr>
            </thead>
            <tbody>
            <?php foreach ($alumnos as $a): ?>
              <tr>
                <td>
                  <div class="cell-name">
                    <span class="mini-avatar">
                      <?= strtoupper(substr($a['apellido'],0,1).substr($a['nombre'],0,1)) ?>
                    </span>
                    <?= htmlspecialchars($a['apellido'].', '.$a['nombre']) ?>
                    <?php if ($a['no_inscripto']): ?>
                      <span class="badge badge-warning" style="font-size:0.7rem;margin-left:4px"
                            title="Importado desde Teams, no figuraba en inscripciones">Teams</span>
                    <?php endif; ?>
                  </div>
                </td>
                <td><?= htmlspecialchars($a['legajo']) ?></td>
                <td><?= $a['hora_entrada'] ?? '—' ?></td>
                <td>
                  <span class="badge <?= $badge_asist[$a['estado']] ?? 'badge-muted' ?>">
                    <?= $label_asist[$a['estado']] ?? ucfirst($a['estado']) ?>
                  </span>
                </td>
              </tr>
            <?php endforeach; ?>
            <?php if (empty($alumnos)): ?>
              <tr><td colspan="4" style="text-align:center;padding:32px;color:var(--c-text-faint)">
                No hay alumnos inscriptos ni asistencia registrada para esta clase.
              </td></tr>
            <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
      <?php endif; ?>

    <?php elseif ($materia_id): ?>
      <!-- ── VISTA CLASES DE LA MATERIA ────────────────────── -->

      <!-- Stats -->
      <div class="stat-grid" style="margin-bottom:24px">
        <div class="stat-card">
          <div class="stat-icon i-navy"><i class="fa-solid fa-chalkboard"></i></div>
          <div><div class="stat-value"><?= $total_fin ?></div><div class="stat-label">Clases finalizadas</div></div>
        </div>
        <div class="stat-card">
          <div class="stat-icon i-green"><i class="fa-solid fa-user-check"></i></div>
          <div><div class="stat-value"><?= $total_pres ?></div><div class="stat-label">Presentes</div></div>
        </div>
        <div class="stat-card">
          <div class="stat-icon i-amber"><i class="fa-solid fa-user-xmark"></i></div>
          <div><div class="stat-value"><?= $total_aus ?></div><div class="stat-label">Ausentes</div></div>
        </div>
        <div class="stat-card">
          <div class="stat-icon i-blue"><i class="fa-solid fa-percent"></i></div>
          <div><div class="stat-value"><?= $prom_pct ?>%</div><div class="stat-label">Asistencia promedio</div></div>
        </div>
      </div>

      <!-- Tabla de clases -->
      <div class="card table-card">
        <div class="table-scroll">
          <table class="data-table">
            <thead>
              <tr><th>Fecha</th><th>Hora</th><th>Inscriptos</th><th>Presentes</th><th>Ausentes</th><th>Asistencia</th><th>Estado</th><th></th></tr>
            </thead>
            <tbody>
            <?php foreach ($clases as $c):
              $pct = (float)($c['pct'] ?? 0);
              [$bc, $bl] = $badge_estado[$c['estado']] ?? ['badge-muted', $c['estado']];
              $fin = $c['estado'] === 'finalizada';
            ?>
              <tr>
                <td><?= date('d/m/Y', strtotime($c['fecha'])) ?></td>
                <td><?= substr($c['hora_inicio'], 0, 5) ?></td>
                <td><?= (int)$c['inscriptos'] ?></td>
                <td><?= $fin ? '<span class="badge badge-success">'.(int)$c['presentes'].'</span>' : '—' ?></td>
                <td><?= $fin ? '<span class="badge badge-danger">'.(int)$c['ausentes'].'</span>'   : '—' ?></td>
                <td>
                  <?php if ($fin): ?>
                  <div style="display:flex;align-items:center;gap:8px">
                    <div class="progress" style="flex:1;min-width:80px">
                      <span style="width:<?= $pct ?>%;background:<?= $pct>=75?'var(--c-success)':($pct>=50?'var(--c-warning)':'var(--c-danger)') ?>"></span>
                    </div>
                    <?= $pct ?>%
                  </div>
                  <?php else: ?>—<?php endif; ?>
                </td>
                <td><span class="badge <?= $bc ?>"><?= $bl ?></span></td>
                <td>
                  <div style="display:flex;gap:6px">
                    <a href="historial.php?materia_id=<?= $materia_id ?>&clase_id=<?= $c['id'] ?>"
                       class="btn btn-ghost btn-sm">
                      <i class="fa-solid fa-users"></i> Ver alumnos
                    </a>
                    <?php if ($fin): ?>
                    <button class="btn btn-success btn-sm"
                            data-export-url="../api/exportar.php?clase_id=<?= $c['id'] ?>">
                      <i class="fa-solid fa-file-excel"></i>
                    </button>
                    <?php endif; ?>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
            <?php if (empty($clases)): ?>
              <tr><td colspan="8" style="text-align:center;padding:32px;color:var(--c-text-faint)">
                No hay clases registradas para esta materia.
              </td></tr>
            <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>

    <?php else: ?>
      <!-- ── VISTA TARJETAS DE MATERIAS ─────────────────────── -->
      <?php if (empty($materias)): ?>
        <div class="empty-state">
          <i class="fa-solid fa-book-open"></i>
          <p>No tenés materias asignadas todavía.</p>
        </div>
      <?php else: ?>
      <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:18px">
        <?php foreach ($materias as $m):
          $pct = (float)($m['pct_asist'] ?? 0);
        ?>
        <a href="historial.php?materia_id=<?= $m['id'] ?>"
           style="text-decoration:none;color:inherit;display:block">
          <div class="card" style="padding:22px;transition:box-shadow .15s,transform .15s;cursor:pointer"
               onmouseover="this.style.boxShadow='var(--sh-lg)';this.style.transform='translateY(-2px)'"
               onmouseout="this.style.boxShadow='';this.style.transform=''">

            <div style="display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:14px">
              <div>
                <div style="font-weight:700;font-size:1.05rem;margin-bottom:2px">
                  <?= htmlspecialchars($m['nombre']) ?>
                </div>
                <div class="text-muted" style="font-size:0.83rem"><?= htmlspecialchars($m['curso']) ?></div>
              </div>
              <span class="badge <?= $m['modalidad']==='virtual'?'badge-muted':'badge-accent' ?>">
                <?= ucfirst($m['modalidad']) ?>
              </span>
            </div>

            <!-- Stats rápidas -->
            <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:10px;margin-bottom:14px">
              <div style="text-align:center;padding:8px;background:var(--c-muted-soft);border-radius:8px">
                <div style="font-weight:700;font-size:1.1rem"><?= (int)$m['clases_fin'] ?></div>
                <div style="font-size:0.72rem;color:var(--c-text-soft)">Clases</div>
              </div>
              <div style="text-align:center;padding:8px;background:var(--c-muted-soft);border-radius:8px">
                <div style="font-weight:700;font-size:1.1rem"><?= (int)$m['inscriptos'] ?></div>
                <div style="font-size:0.72rem;color:var(--c-text-soft)">Alumnos</div>
              </div>
              <div style="text-align:center;padding:8px;background:var(--c-muted-soft);border-radius:8px">
                <div style="font-weight:700;font-size:1.1rem;color:<?= $pct>=75?'var(--c-success)':($pct>=50?'var(--c-warning)':'var(--c-danger)') ?>">
                  <?= $m['clases_fin'] > 0 ? $pct.'%' : '—' ?>
                </div>
                <div style="font-size:0.72rem;color:var(--c-text-soft)">Asistencia</div>
              </div>
            </div>

            <!-- Próxima clase -->
            <div style="display:flex;align-items:center;justify-content:space-between;
                        font-size:0.82rem;color:var(--c-text-soft)">
              <?php if ($m['proxima']): ?>
                <span><i class="fa-regular fa-calendar" style="margin-right:4px"></i>
                  Próxima: <strong><?= date('d/m/Y', strtotime($m['proxima'])) ?></strong>
                </span>
              <?php else: ?>
                <span class="text-muted">Sin próximas clases</span>
              <?php endif; ?>
              <span style="color:var(--c-primary);font-weight:600">
                Ver historial <i class="fa-solid fa-chevron-right" style="font-size:0.7rem"></i>
              </span>
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
<script src="../assets/js/export.js"></script>
</body>
</html>
