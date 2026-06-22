<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';
require_auth(['profesor', 'admin']);

$pdo     = getPDO();
$prof_id = $_SESSION['usuario_id'];

// ── Filtros ───────────────────────────────────────────────────
$f_materia = (int) ($_GET['materia'] ?? 0) ?: null;
$f_fecha   = $_GET['fecha']  ?? '';
$f_estado  = $_GET['estado'] ?? '';
$pagina    = max(1, (int) ($_GET['pagina'] ?? 1));
$por_pag   = 20;
$offset    = ($pagina - 1) * $por_pag;

// Materias del profesor para el select
$mis_materias = $pdo->prepare(
    "SELECT id, CONCAT(nombre,' · ',curso) AS label
     FROM materias WHERE profesor_id = ? AND activo = 1 ORDER BY nombre"
);
$mis_materias->execute([$prof_id]);
$mis_materias = $mis_materias->fetchAll();

// ── Contadores del filtro activo ──────────────────────────────
$where  = 'WHERE m.profesor_id = ?';
$params = [$prof_id];
if ($f_materia) { $where .= ' AND c.materia_id = ?'; $params[] = $f_materia; }
if ($f_fecha)   { $where .= ' AND c.fecha = ?';      $params[] = $f_fecha; }
if ($f_estado)  { $where .= ' AND a.estado = ?';     $params[] = $f_estado; }

$cnt = $pdo->prepare(
    "SELECT COUNT(*) FROM asistencias a
     JOIN clases c ON c.id = a.clase_id
     JOIN materias m ON m.id = c.materia_id
     $where"
);
$cnt->execute($params);
$total         = (int) $cnt->fetchColumn();
$total_paginas = max(1, (int) ceil($total / $por_pag));

// Contadores presentes/ausentes con los mismos filtros (sin estado)
$where_sin_estado  = 'WHERE m.profesor_id = ?';
$params_sin_estado = [$prof_id];
if ($f_materia) { $where_sin_estado .= ' AND c.materia_id = ?'; $params_sin_estado[] = $f_materia; }
if ($f_fecha)   { $where_sin_estado .= ' AND c.fecha = ?';      $params_sin_estado[] = $f_fecha; }

$stats = $pdo->prepare(
    "SELECT
        SUM(a.estado IN ('presente','tardanza')) AS presentes,
        SUM(a.estado = 'ausente')                AS ausentes
     FROM asistencias a
     JOIN clases c ON c.id = a.clase_id
     JOIN materias m ON m.id = c.materia_id
     $where_sin_estado"
);
$stats->execute($params_sin_estado);
$st = $stats->fetch();

// ── Filas paginadas ───────────────────────────────────────────
$stmt = $pdo->prepare(
    "SELECT u.apellido, u.nombre, u.legajo,
            m.nombre AS materia,
            c.fecha,
            COALESCE(TIME_FORMAT(a.hora_entrada,'%H:%i'),'—') AS hora,
            a.estado
     FROM asistencias a
     JOIN usuarios u ON u.id = a.alumno_id
     JOIN clases c ON c.id = a.clase_id
     JOIN materias m ON m.id = c.materia_id
     $where
     ORDER BY c.fecha DESC, u.apellido
     LIMIT :lim OFFSET :off"
);
foreach ($params as $i => $v) $stmt->bindValue($i + 1, $v);
$stmt->bindValue(':lim', $por_pag, PDO::PARAM_INT);
$stmt->bindValue(':off', $offset,  PDO::PARAM_INT);
$stmt->execute();
$filas = $stmt->fetchAll();

// URL export con filtros actuales
$export_params = array_filter([
    'materia_id' => $f_materia,
    'fecha'      => $f_fecha,
]);
if (!$export_params) $export_params['materia_id'] = $f_materia ?: ($mis_materias[0]['id'] ?? 0);
$export_url = '../api/exportar.php?' . http_build_query($export_params);

$partes    = explode(' ', $_SESSION['nombre']);
$iniciales = strtoupper(substr($partes[0],0,1).substr($partes[1]??'',0,1));

function url_pag_h(int $p): string {
    $params = $_GET; $params['pagina'] = $p;
    return '?' . http_build_query($params);
}

$badge = ['presente' => 'badge-success', 'tardanza' => 'badge-warning', 'ausente' => 'badge-danger'];
$label = ['presente' => 'Presente', 'tardanza' => 'Tardanza', 'ausente' => 'Ausente'];
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
      <a href="materias.php"><i class="fa-solid fa-book"></i> Programar clase</a>
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
      <div class="page-title">Historial de asistencia</div>
      <div class="topbar-right">
        <?php if (!empty($mis_materias)): ?>
        <button class="btn btn-success btn-sm"
                data-export-url="<?= htmlspecialchars($export_url) ?>">
          <i class="fa-solid fa-file-excel"></i> Exportar a Excel
        </button>
        <?php endif; ?>
      </div>
    </header>

    <main class="app-content">
      <form method="GET" action="historial.php">
        <div class="toolbar">
          <div class="filters">
            <select class="select" name="materia">
              <option value="">Todas las materias</option>
              <?php foreach ($mis_materias as $m): ?>
                <option value="<?= $m['id'] ?>" <?= $f_materia == $m['id'] ? 'selected' : '' ?>>
                  <?= htmlspecialchars($m['label']) ?>
                </option>
              <?php endforeach; ?>
            </select>
            <input class="input" type="date" name="fecha" value="<?= htmlspecialchars($f_fecha) ?>" />
            <select class="select" name="estado">
              <option value="">Todos los estados</option>
              <option value="presente"  <?= $f_estado === 'presente'  ? 'selected' : '' ?>>Presente</option>
              <option value="tardanza"  <?= $f_estado === 'tardanza'  ? 'selected' : '' ?>>Tardanza</option>
              <option value="ausente"   <?= $f_estado === 'ausente'   ? 'selected' : '' ?>>Ausente</option>
            </select>
            <button type="submit" class="btn btn-ghost btn-sm"><i class="fa-solid fa-filter"></i> Filtrar</button>
          </div>
          <div class="spacer"></div>
          <span class="badge badge-success"><?= (int)($st['presentes'] ?? 0) ?> presentes</span>
          <span class="badge badge-danger"><?= (int)($st['ausentes']  ?? 0) ?> ausentes</span>
        </div>
      </form>

      <div class="card table-card">
        <div class="table-scroll">
          <table class="data-table">
            <thead>
              <tr><th>Alumno</th><th>Legajo</th><th>Materia</th><th>Fecha</th><th>Hora</th><th>Estado</th></tr>
            </thead>
            <tbody>
            <?php foreach ($filas as $f): ?>
              <tr>
                <td>
                  <div class="cell-name">
                    <span class="mini-avatar">
                      <?= strtoupper(substr($f['nombre'],0,1).substr($f['apellido'],0,1)) ?>
                    </span>
                    <?= htmlspecialchars($f['nombre'].' '.$f['apellido']) ?>
                  </div>
                </td>
                <td><?= htmlspecialchars($f['legajo']) ?></td>
                <td><?= htmlspecialchars($f['materia']) ?></td>
                <td><?= date('d/m/Y', strtotime($f['fecha'])) ?></td>
                <td><?= htmlspecialchars($f['hora']) ?></td>
                <td>
                  <span class="badge <?= $badge[$f['estado']] ?? 'badge-muted' ?>">
                    <?= $label[$f['estado']] ?? ucfirst($f['estado']) ?>
                  </span>
                </td>
              </tr>
            <?php endforeach; ?>
            <?php if (empty($filas)): ?>
              <tr><td colspan="6" style="text-align:center;padding:32px;color:var(--c-text-faint)">
                Sin registros con esos filtros.
              </td></tr>
            <?php endif; ?>
            </tbody>
          </table>
        </div>
        <div class="pagination">
          <span class="pg-info">Mostrando <?= $offset+1 ?>–<?= min($offset+$por_pag,$total) ?> de <?= $total ?></span>
          <div class="pg-controls">
            <a href="<?= url_pag_h(max(1,$pagina-1)) ?>" class="pg-btn <?= $pagina<=1?'disabled':'' ?>"><i class="fa-solid fa-chevron-left"></i></a>
            <?php for($i=1;$i<=$total_paginas;$i++): ?>
              <a href="<?= url_pag_h($i) ?>" class="pg-btn <?= $i===$pagina?'active':'' ?>"><?= $i ?></a>
            <?php endfor; ?>
            <a href="<?= url_pag_h(min($total_paginas,$pagina+1)) ?>" class="pg-btn <?= $pagina>=$total_paginas?'disabled':'' ?>"><i class="fa-solid fa-chevron-right"></i></a>
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
