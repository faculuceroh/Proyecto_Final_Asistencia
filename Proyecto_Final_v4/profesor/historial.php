<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';
require_auth(['profesor', 'admin']);

$pdo     = getPDO();
$prof_id = $_SESSION['usuario_id'];

$materia_id = (int)($_GET['materia_id'] ?? 0) ?: null;
$clase_id   = (int)($_GET['clase_id']   ?? 0) ?: null;

$partes    = explode(' ', $_SESSION['nombre']);
$iniciales = strtoupper(substr($partes[0],0,1).substr($partes[1]??'',0,1));

$badge_estado = [
    'pendiente'  => ['badge-muted',    'Pendiente'],
    'en_curso'   => ['badge-warning',  'En curso'],
    'finalizada' => ['badge-success',  'Finalizada'],
];
$badge_asist = ['presente'=>'badge-success','tardanza'=>'badge-warning','ausente'=>'badge-danger'];
$label_asist = ['presente'=>'Presente','tardanza'=>'Tardanza','ausente'=>'Ausente'];

// ── Vista detalle de clase (alumnos) ─────────────────────────
if ($materia_id && $clase_id) {

    $stmt = $pdo->prepare(
        "SELECT m.id, m.nombre, m.curso
         FROM materias m
         WHERE m.id = ? AND (m.profesor_id = ? OR m.profesor_2_id = ?) AND m.activo = 1 LIMIT 1"
    );
    $stmt->execute([$materia_id, $prof_id, $prof_id]);
    $materia = $stmt->fetch();
    if (!$materia) { header('Location: historial.php'); exit; }

    $stmt = $pdo->prepare("SELECT id, fecha, hora_inicio, estado FROM clases WHERE id = ? AND materia_id = ? LIMIT 1");
    $stmt->execute([$clase_id, $materia_id]);
    $clase = $stmt->fetch();
    if (!$clase) { header("Location: historial.php?materia_id=$materia_id"); exit; }

    // Todos los alumnos inscriptos + su asistencia en esta clase
    $stmt = $pdo->prepare(
        "SELECT u.apellido, u.nombre, u.legajo,
                COALESCE(a.estado, 'ausente') AS estado,
                TIME_FORMAT(a.hora_entrada, '%H:%i') AS hora_entrada
         FROM inscripciones i
         JOIN usuarios u ON u.id = i.alumno_id
         LEFT JOIN asistencias a ON a.alumno_id = i.alumno_id AND a.clase_id = ?
         WHERE i.materia_id = ?
         ORDER BY u.apellido, u.nombre"
    );
    $stmt->execute([$clase_id, $materia_id]);
    $alumnos = $stmt->fetchAll();

    $presentes = count(array_filter($alumnos, fn($a) => in_array($a['estado'], ['presente','tardanza'])));
    $ausentes  = count(array_filter($alumnos, fn($a) => $a['estado'] === 'ausente'));
    $total_al  = count($alumnos);
    $pct_clase = $total_al ? round($presentes / $total_al * 100, 1) : 0;
}

// ── Vista clases de una materia ───────────────────────────────
elseif ($materia_id) {

    $stmt = $pdo->prepare(
        "SELECT m.id, m.nombre, m.curso, m.modalidad
         FROM materias m
         WHERE m.id = ? AND (m.profesor_id = ? OR m.profesor_2_id = ?) AND m.activo = 1 LIMIT 1"
    );
    $stmt->execute([$materia_id, $prof_id, $prof_id]);
    $materia = $stmt->fetch();
    if (!$materia) { header('Location: historial.php'); exit; }

    $stmt = $pdo->prepare(
        "SELECT c.id, c.fecha, c.hora_inicio, c.duracion_min, c.estado,
                (SELECT COUNT(*) FROM inscripciones WHERE materia_id = c.materia_id) AS inscriptos,
                COALESCE(SUM(a.estado IN ('presente','tardanza')), 0) AS presentes,
                COALESCE(SUM(a.estado = 'ausente'), 0) AS ausentes,
                ROUND(SUM(a.estado IN ('presente','tardanza')) /
                      NULLIF((SELECT COUNT(*) FROM inscripciones WHERE materia_id = c.materia_id), 0) * 100, 1) AS pct
         FROM clases c
         LEFT JOIN asistencias a ON a.clase_id = c.id
         WHERE c.materia_id = ? AND c.fecha <= CURDATE()
         GROUP BY c.id
         ORDER BY c.fecha DESC"
    );
    $stmt->execute([$materia_id]);
    $clases = $stmt->fetchAll();

    $finalizadas = array_filter($clases, fn($c) => $c['estado'] === 'finalizada');
    $total_fin   = count($finalizadas);
    $total_pres  = array_sum(array_column($finalizadas, 'presentes'));
    $total_aus   = array_sum(array_column($finalizadas, 'ausentes'));
    $prom_pct    = $total_fin
        ? round(array_sum(array_column($finalizadas, 'pct')) / $total_fin, 1) : 0;
}

// ── Vista listado de materias ─────────────────────────────────
else {
    $stmt = $pdo->prepare(
        "SELECT m.id, m.nombre, m.curso, m.modalidad,
                (SELECT COUNT(*) FROM clases WHERE materia_id = m.id AND estado = 'finalizada') AS clases_fin,
                (SELECT COUNT(*) FROM inscripciones WHERE materia_id = m.id) AS inscriptos,
                (SELECT ROUND(
                    SUM(a2.estado IN ('presente','tardanza')) /
                    NULLIF(COUNT(a2.id), 0) * 100, 1)
                 FROM asistencias a2
                 JOIN clases c2 ON c2.id = a2.clase_id
                 WHERE c2.materia_id = m.id AND c2.estado = 'finalizada') AS pct_asist,
                (SELECT MIN(fecha) FROM clases
                 WHERE materia_id = m.id AND fecha >= CURDATE() AND estado = 'pendiente') AS proxima
         FROM materias m
         WHERE (m.profesor_id = ? OR m.profesor_2_id = ?) AND m.activo = 1
         ORDER BY m.nombre"
    );
    $stmt->execute([$prof_id, $prof_id]);
    $materias = $stmt->fetchAll();
}
?>
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

      <?php if ($clase['estado'] === 'pendiente'): ?>
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
                No hay alumnos inscriptos en esta materia.
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
<script src="../assets/js/export.js"></script>
</body>
</html>
