<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';
require_auth(['admin']);

$pdo = getPDO();

// ── Filtros y paginación ──────────────────────────────────────
$q       = trim($_GET['q']      ?? '');
$curso   = trim($_GET['curso']  ?? '');
$pagina  = max(1, (int) ($_GET['pagina'] ?? 1));
$por_pag = 15;
$offset  = ($pagina - 1) * $por_pag;

// ── Stats ─────────────────────────────────────────────────────
$total_materias   = (int) $pdo->query("SELECT COUNT(*) FROM materias WHERE activo=1")->fetchColumn();
$total_profesores = (int) $pdo->query("SELECT COUNT(DISTINCT profesor_id) FROM materias WHERE activo=1 AND profesor_id IS NOT NULL")->fetchColumn();
$total_cursos     = (int) $pdo->query("SELECT COUNT(DISTINCT curso) FROM materias WHERE activo=1")->fetchColumn();

// ── Cursos disponibles para el filtro ────────────────────────
$cursos_lista = $pdo->query("SELECT DISTINCT curso FROM materias WHERE activo=1 ORDER BY curso")->fetchAll(PDO::FETCH_COLUMN);

// ── Consulta de materias ──────────────────────────────────────
$where  = 'WHERE m.activo = 1';
$params = [];

if ($q !== '') {
    $where   .= ' AND (m.nombre LIKE ? OR m.codigo LIKE ?)';
    $like     = '%' . $q . '%';
    $params[] = $like;
    $params[] = $like;
}
if ($curso !== '') {
    $where   .= ' AND m.curso = ?';
    $params[] = $curso;
}

$stmt = $pdo->prepare("SELECT COUNT(*) FROM materias m $where");
$stmt->execute($params);
$total         = (int) $stmt->fetchColumn();
$total_paginas = max(1, (int) ceil($total / $por_pag));

$stmt = $pdo->prepare(
    "SELECT m.id, m.nombre, m.codigo, m.curso, m.modalidad,
            COALESCE(CONCAT(u.nombre,' ',u.apellido), '—') AS profesor,
            COUNT(i.id) AS total_alumnos
     FROM materias m
     LEFT JOIN usuarios u ON u.id = m.profesor_id
     LEFT JOIN inscripciones i ON i.materia_id = m.id
     $where
     GROUP BY m.id
     ORDER BY m.nombre ASC
     LIMIT :lim OFFSET :off"
);
foreach ($params as $i => $v) $stmt->bindValue($i + 1, $v);
$stmt->bindValue(':lim', $por_pag, PDO::PARAM_INT);
$stmt->bindValue(':off', $offset,  PDO::PARAM_INT);
$stmt->execute();
$materias = $stmt->fetchAll();

$partes    = explode(' ', $_SESSION['nombre']);
$iniciales = strtoupper(substr($partes[0], 0, 1) . substr($partes[1] ?? '', 0, 1));

function url_pag(int $p): string {
    $params = $_GET; $params['pagina'] = $p;
    return '?' . http_build_query($params);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Materias · Administración</title>
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
      <div><div class="name">Asistencia QR</div><div class="sub">Administración</div></div>
    </div>
    <nav class="sidebar-nav">
      <span class="nav-label">Sistema</span>
      <a href="dashboard.php"><i class="fa-solid fa-gauge-high"></i> Resumen</a>
      <a href="usuarios.php"><i class="fa-solid fa-users"></i> Usuarios</a>
      <a href="materias.php" class="active"><i class="fa-solid fa-book"></i> Materias</a>
      <a href="configuracion.php"><i class="fa-solid fa-gear"></i> Configuración</a>
      <span class="nav-label">Cuenta</span>
      <a href="../logout.php"><i class="fa-solid fa-right-from-bracket"></i> Cerrar sesión</a>
    </nav>
    <div class="sidebar-user">
      <div class="avatar"><?= htmlspecialchars($iniciales) ?></div>
      <div class="meta">
        <div class="u-name"><?= htmlspecialchars($_SESSION['nombre']) ?></div>
        <div class="u-role">Administrador</div>
      </div>
      <a href="../logout.php" class="logout"><i class="fa-solid fa-right-from-bracket"></i></a>
    </div>
  </aside>
  <div class="sidebar-backdrop" data-sidebar-backdrop></div>

  <div class="app-main">
    <header class="topbar">
      <button class="hamburger" data-sidebar-toggle aria-label="Menú"><i class="fa-solid fa-bars"></i></button>
      <div class="page-title">Materias <small>Todas las materias del período</small></div>
    </header>

    <main class="app-content">

      <!-- Stats -->
      <div class="stat-grid">
        <div class="stat-card">
          <div class="stat-icon i-navy"><i class="fa-solid fa-book"></i></div>
          <div><div class="stat-value"><?= $total_materias ?></div><div class="stat-label">Materias activas</div></div>
        </div>
        <div class="stat-card">
          <div class="stat-icon i-blue"><i class="fa-solid fa-chalkboard-user"></i></div>
          <div><div class="stat-value"><?= $total_profesores ?></div><div class="stat-label">Profesores asignados</div></div>
        </div>
        <div class="stat-card">
          <div class="stat-icon i-green"><i class="fa-solid fa-layer-group"></i></div>
          <div><div class="stat-value"><?= $total_cursos ?></div><div class="stat-label">Cursos</div></div>
        </div>
      </div>

      <!-- Filtros -->
      <form method="GET" action="materias.php" id="filtrosForm">
        <div class="toolbar">
          <div class="filters">
            <input class="input" type="search" name="q" id="search"
                   placeholder="Buscar materia…"
                   value="<?= htmlspecialchars($q) ?>"
                   style="min-width:220px" />
            <select class="select" name="curso" id="fCurso">
              <option value="">Todos los cursos</option>
              <?php foreach ($cursos_lista as $c): ?>
                <option value="<?= htmlspecialchars($c) ?>" <?= $curso === $c ? 'selected' : '' ?>>
                  <?= htmlspecialchars($c) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
      </form>

      <!-- Tabla -->
      <div class="card table-card">
        <div class="table-scroll">
          <table class="data-table" id="matTable">
            <thead>
              <tr><th>Materia</th><th>Código</th><th>Curso</th><th>Profesor</th><th>Alumnos</th><th>Modalidad</th></tr>
            </thead>
            <tbody>
            <?php foreach ($materias as $m): ?>
              <tr>
                <td><div class="cell-name"><?= htmlspecialchars($m['nombre']) ?></div></td>
                <td><?= htmlspecialchars($m['codigo']) ?></td>
                <td><?= htmlspecialchars($m['curso']) ?></td>
                <td><?= htmlspecialchars($m['profesor']) ?></td>
                <td><span class="badge badge-accent"><?= (int)$m['total_alumnos'] ?></span></td>
                <td>
                  <span class="badge <?= $m['modalidad'] === 'virtual' ? 'badge-muted' : 'badge-accent' ?>">
                    <?= ucfirst($m['modalidad']) ?>
                  </span>
                </td>
              </tr>
            <?php endforeach; ?>
            <?php if (empty($materias)): ?>
              <tr><td colspan="6" style="text-align:center;padding:32px;color:var(--c-text-faint)">
                No se encontraron materias.
              </td></tr>
            <?php endif; ?>
            </tbody>
          </table>
        </div>

        <div class="pagination">
          <span class="pg-info">
            Mostrando <?= $offset + 1 ?>–<?= min($offset + $por_pag, $total) ?> de <?= $total ?>
          </span>
          <div class="pg-controls">
            <a href="<?= url_pag(max(1, $pagina - 1)) ?>"
               class="pg-btn <?= $pagina <= 1 ? 'disabled' : '' ?>">
              <i class="fa-solid fa-chevron-left"></i>
            </a>
            <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
              <a href="<?= url_pag($i) ?>" class="pg-btn <?= $i === $pagina ? 'active' : '' ?>">
                <?= $i ?>
              </a>
            <?php endfor; ?>
            <a href="<?= url_pag(min($total_paginas, $pagina + 1)) ?>"
               class="pg-btn <?= $pagina >= $total_paginas ? 'disabled' : '' ?>">
              <i class="fa-solid fa-chevron-right"></i>
            </a>
          </div>
        </div>
      </div>
    </main>
  </div>
</div>

<script src="../assets/js/utils.js"></script>
<script>
  App.qs('#fCurso').addEventListener('change', () => App.qs('#filtrosForm').submit());
  let t;
  App.qs('#search').addEventListener('input', function () {
    clearTimeout(t);
    t = setTimeout(() => App.qs('#filtrosForm').submit(), 500);
  });
</script>
</body>
</html>
