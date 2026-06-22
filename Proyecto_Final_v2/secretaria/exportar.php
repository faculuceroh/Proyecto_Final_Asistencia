<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';
require_auth(['secretaria', 'admin']);

$pdo = getPDO();

// ── Filtros ───────────────────────────────────────────────────
$f_profesor = (int) ($_GET['profesor'] ?? 0) ?: null;
$f_materia  = (int) ($_GET['materia']  ?? 0) ?: null;
$f_fecha    = $_GET['fecha'] ?? '';
$pagina     = max(1, (int) ($_GET['pagina'] ?? 1));
$por_pag    = 15;
$offset     = ($pagina - 1) * $por_pag;

// ── Listas para los selects ───────────────────────────────────
$profesores = $pdo->query(
    "SELECT id, CONCAT(nombre,' ',apellido) AS nombre
     FROM usuarios WHERE rol='profesor' AND activo=1 ORDER BY apellido"
)->fetchAll();

$materias_lista = $pdo->query(
    "SELECT id, nombre FROM materias WHERE activo=1 ORDER BY nombre"
)->fetchAll();

// ── Stats globales ────────────────────────────────────────────
$total_clases  = (int) $pdo->query("SELECT COUNT(*) FROM clases WHERE estado='finalizada'")->fetchColumn();
$total_pres    = (int) $pdo->query("SELECT COUNT(*) FROM asistencias WHERE estado IN ('presente','tardanza')")->fetchColumn();
$total_aus     = (int) $pdo->query("SELECT COUNT(*) FROM asistencias WHERE estado='ausente'")->fetchColumn();
$prom_asist    = $pdo->query(
    "SELECT ROUND(SUM(estado IN ('presente','tardanza'))/NULLIF(COUNT(*),0)*100,1) FROM asistencias"
)->fetchColumn() ?? 0;

// ── Tabla de clases con filtros ───────────────────────────────
$where  = "WHERE c.estado = 'finalizada'";
$params = [];

if ($f_profesor) { $where .= ' AND m.profesor_id = ?';  $params[] = $f_profesor; }
if ($f_materia)  { $where .= ' AND c.materia_id  = ?';  $params[] = $f_materia; }
if ($f_fecha)    { $where .= ' AND c.fecha        = ?';  $params[] = $f_fecha; }

$stmt = $pdo->prepare(
    "SELECT COUNT(*) FROM clases c JOIN materias m ON m.id=c.materia_id $where"
);
$stmt->execute($params);
$total         = (int) $stmt->fetchColumn();
$total_paginas = max(1, (int) ceil($total / $por_pag));

$stmt = $pdo->prepare(
    "SELECT c.id,
            m.nombre AS materia, m.curso,
            CONCAT(u.nombre,' ',u.apellido) AS profesor,
            c.fecha,
            COUNT(DISTINCT i.alumno_id)                                              AS total_alum,
            SUM(a.estado IN ('presente','tardanza'))                                  AS presentes,
            COUNT(DISTINCT i.alumno_id)-COALESCE(SUM(a.estado IN ('presente','tardanza')),0) AS ausentes,
            ROUND(SUM(a.estado IN ('presente','tardanza'))/NULLIF(COUNT(DISTINCT i.alumno_id),0)*100,1) AS pct
     FROM clases c
     JOIN materias m ON m.id = c.materia_id
     LEFT JOIN usuarios u ON u.id = m.profesor_id
     LEFT JOIN inscripciones i ON i.materia_id = m.id
     LEFT JOIN asistencias a ON a.clase_id = c.id AND a.alumno_id = i.alumno_id
     $where
     GROUP BY c.id
     ORDER BY c.fecha DESC, m.nombre
     LIMIT ? OFFSET ?"
);
$stmt->execute(array_merge($params, [$por_pag, $offset]));
$clases = $stmt->fetchAll();

$partes    = explode(' ', $_SESSION['nombre']);
$iniciales = strtoupper(substr($partes[0],0,1) . substr($partes[1]??'',0,1));

function url_pag(int $p): string {
    $params = $_GET; $params['pagina'] = $p;
    return '?' . http_build_query($params);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" /><meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Clases del período · Secretaría</title>
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
      <a href="exportar.php" class="active"><i class="fa-solid fa-file-export"></i> Clases del período</a>
      <a href="materias.php"><i class="fa-solid fa-book"></i> Materias</a>
      <a href="inscripciones.php"><i class="fa-solid fa-user-plus"></i> Inscripciones</a>
      <a href="usuarios.php"><i class="fa-solid fa-users"></i> Alta de usuarios</a>
      <a href="reportes.php"><i class="fa-solid fa-chart-pie"></i> Reportes</a>
      <span class="nav-label">Cuenta</span>
      <a href="../logout.php"><i class="fa-solid fa-right-from-bracket"></i> Cerrar sesión</a>
    </nav>
    <div class="sidebar-user">
      <div class="avatar"><?= htmlspecialchars($iniciales) ?></div>
      <div class="meta">
        <div class="u-name"><?= htmlspecialchars($_SESSION['nombre']) ?></div>
        <div class="u-role">Secretaría</div>
      </div>
      <a href="../logout.php" class="logout"><i class="fa-solid fa-right-from-bracket"></i></a>
    </div>
  </aside>
  <div class="sidebar-backdrop" data-sidebar-backdrop></div>

  <div class="app-main">
    <header class="topbar">
      <button class="hamburger" data-sidebar-toggle aria-label="Menú"><i class="fa-solid fa-bars"></i></button>
      <div class="page-title">Clases del período <small>Exportá y enviá los registros de asistencia</small></div>
    </header>
    <main class="app-content">

      <div class="stat-grid">
        <div class="stat-card"><div class="stat-icon i-navy"><i class="fa-solid fa-book"></i></div><div><div class="stat-value"><?= $total_clases ?></div><div class="stat-label">Clases finalizadas</div></div></div>
        <div class="stat-card"><div class="stat-icon i-green"><i class="fa-solid fa-user-check"></i></div><div><div class="stat-value"><?= number_format($total_pres) ?></div><div class="stat-label">Presentes</div></div></div>
        <div class="stat-card"><div class="stat-icon i-amber"><i class="fa-solid fa-user-xmark"></i></div><div><div class="stat-value"><?= number_format($total_aus) ?></div><div class="stat-label">Ausentes</div></div></div>
        <div class="stat-card"><div class="stat-icon i-blue"><i class="fa-solid fa-percent"></i></div><div><div class="stat-value"><?= $prom_asist ?>%</div><div class="stat-label">Asistencia promedio</div></div></div>
      </div>

      <form method="GET" action="exportar.php" id="filtrosForm">
        <div class="toolbar">
          <div class="filters">
            <select class="select" name="profesor">
              <option value="">Todos los profesores</option>
              <?php foreach ($profesores as $p): ?>
                <option value="<?= $p['id'] ?>" <?= $f_profesor == $p['id'] ? 'selected' : '' ?>>
                  <?= htmlspecialchars($p['nombre']) ?>
                </option>
              <?php endforeach; ?>
            </select>
            <select class="select" name="materia">
              <option value="">Todas las materias</option>
              <?php foreach ($materias_lista as $m): ?>
                <option value="<?= $m['id'] ?>" <?= $f_materia == $m['id'] ? 'selected' : '' ?>>
                  <?= htmlspecialchars($m['nombre']) ?>
                </option>
              <?php endforeach; ?>
            </select>
            <input class="input" type="date" name="fecha" value="<?= htmlspecialchars($f_fecha) ?>" />
            <button type="submit" class="btn btn-ghost btn-sm"><i class="fa-solid fa-filter"></i> Filtrar</button>
          </div>
        </div>
      </form>

      <div class="card table-card">
        <div class="table-scroll">
          <table class="data-table">
            <thead>
              <tr><th>Materia</th><th>Profesor</th><th>Fecha</th><th>Presentes</th><th>Ausentes</th><th>Asistencia</th><th>Acciones</th></tr>
            </thead>
            <tbody>
            <?php foreach ($clases as $c): ?>
              <?php $pct = (float)($c['pct'] ?? 0); ?>
              <tr>
                <td>
                  <div class="cell-name"><?= htmlspecialchars($c['materia']) ?></div>
                  <small class="text-muted"><?= htmlspecialchars($c['curso']) ?></small>
                </td>
                <td><?= htmlspecialchars($c['profesor'] ?? '—') ?></td>
                <td><?= date('d/m/Y', strtotime($c['fecha'])) ?></td>
                <td><span class="badge badge-success"><?= (int)$c['presentes'] ?></span></td>
                <td><span class="badge badge-danger"><?= (int)$c['ausentes'] ?></span></td>
                <td>
                  <div style="display:flex;align-items:center;gap:8px">
                    <div class="progress" style="flex:1">
                      <span style="width:<?= $pct ?>%;background:<?= $pct>=75?'var(--c-success)':($pct>=50?'var(--c-warning)':'var(--c-danger)') ?>"></span>
                    </div>
                    <?= $pct ?>%
                  </div>
                </td>
                <td>
                  <div style="display:flex;gap:6px">
                    <button class="btn btn-success btn-sm"
                            data-export-url="../api/exportar.php?clase_id=<?= $c['id'] ?>">
                      <i class="fa-solid fa-file-excel"></i> Excel
                    </button>
                    <button class="btn btn-ghost btn-sm"
                            data-send-url="../api/enviar_secretaria.php"
                            data-clase-id="<?= $c['id'] ?>">
                      <i class="fa-solid fa-paper-plane"></i> Enviar
                    </button>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
            <?php if (empty($clases)): ?>
              <tr><td colspan="7" style="text-align:center;padding:32px;color:var(--c-text-faint)">
                No hay clases finalizadas con esos filtros.
              </td></tr>
            <?php endif; ?>
            </tbody>
          </table>
        </div>
        <div class="pagination">
          <span class="pg-info">Mostrando <?= $offset+1 ?>–<?= min($offset+$por_pag,$total) ?> de <?= $total ?></span>
          <div class="pg-controls">
            <a href="<?= url_pag(max(1,$pagina-1)) ?>" class="pg-btn <?= $pagina<=1?'disabled':'' ?>"><i class="fa-solid fa-chevron-left"></i></a>
            <?php for($i=1;$i<=$total_paginas;$i++): ?>
              <a href="<?= url_pag($i) ?>" class="pg-btn <?= $i===$pagina?'active':'' ?>"><?= $i ?></a>
            <?php endfor; ?>
            <a href="<?= url_pag(min($total_paginas,$pagina+1)) ?>" class="pg-btn <?= $pagina>=$total_paginas?'disabled':'' ?>"><i class="fa-solid fa-chevron-right"></i></a>
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

