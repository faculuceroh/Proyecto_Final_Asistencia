<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';
require_auth(['alumno']);

$pdo    = getPDO();
$alu_id = $_SESSION['usuario_id'];

// Filtros
$f_materia = (int)($_GET['materia_id'] ?? 0) ?: null;
$f_estado  = trim($_GET['estado'] ?? '');
$pagina    = max(1, (int)($_GET['pagina'] ?? 1));
$por_pag   = 20;
$offset    = ($pagina - 1) * $por_pag;

$estados_validos = ['presente', 'ausente', 'tardanza'];
if (!in_array($f_estado, $estados_validos, true)) $f_estado = '';

// Materias del alumno para el filtro
$stmt = $pdo->prepare(
    "SELECT m.id, m.nombre FROM inscripciones i
     JOIN materias m ON m.id = i.materia_id
     WHERE i.alumno_id = ? ORDER BY m.nombre"
);
$stmt->execute([$alu_id]);
$mis_materias = $stmt->fetchAll();

// WHERE dinámico
$where  = "WHERE a.alumno_id = ?";
$params = [$alu_id];

if ($f_materia) { $where .= ' AND m.id = ?';       $params[] = $f_materia; }
if ($f_estado)  { $where .= ' AND a.estado = ?';   $params[] = $f_estado; }

// Total
$stmt = $pdo->prepare(
    "SELECT COUNT(*) FROM asistencias a
     JOIN clases c ON c.id = a.clase_id
     JOIN materias m ON m.id = c.materia_id
     $where"
);
$stmt->execute($params);
$total         = (int)$stmt->fetchColumn();
$total_paginas = max(1, (int)ceil($total / $por_pag));

// Filas
$stmt = $pdo->prepare(
    "SELECT m.nombre AS materia, m.curso,
            c.fecha,
            TIME_FORMAT(c.hora_inicio,'%H:%i') AS hora,
            a.estado,
            COALESCE(TIME_FORMAT(a.hora_entrada,'%H:%i'),'—') AS hora_entrada
     FROM asistencias a
     JOIN clases c ON c.id = a.clase_id
     JOIN materias m ON m.id = c.materia_id
     $where
     ORDER BY c.fecha DESC, m.nombre
     LIMIT ? OFFSET ?"
);
$stmt->execute(array_merge($params, [$por_pag, $offset]));
$filas = $stmt->fetchAll();

// Stats de la materia seleccionada (o global)
$stmt = $pdo->prepare(
    "SELECT
       COUNT(*) AS total,
       SUM(a.estado IN ('presente','tardanza')) AS presentes,
       SUM(a.estado = 'ausente') AS ausentes,
       ROUND(SUM(a.estado IN ('presente','tardanza'))/NULLIF(COUNT(*),0)*100,1) AS pct
     FROM asistencias a
     JOIN clases c ON c.id = a.clase_id
     JOIN materias m ON m.id = c.materia_id
     WHERE a.alumno_id = ?" . ($f_materia ? ' AND m.id = ?' : '')
);
$stmt->execute($f_materia ? [$alu_id, $f_materia] : [$alu_id]);
$stats = $stmt->fetch();

$partes    = explode(' ', $_SESSION['nombre']);
$iniciales = strtoupper(substr($partes[0],0,1).substr($partes[1]??'',0,1));

$materia_nombre = '';
if ($f_materia) {
    foreach ($mis_materias as $mm) {
        if ($mm['id'] == $f_materia) { $materia_nombre = $mm['nombre']; break; }
    }
}

function url_pag(int $p): string {
    $q = $_GET; $q['pagina'] = $p;
    return '?' . http_build_query($q);
}

$badge_estado = [
    'presente' => 'badge-success',
    'tardanza' => 'badge-warning',
    'ausente'  => 'badge-danger',
];
$label_estado = [
    'presente' => 'Presente',
    'tardanza' => 'Tardanza',
    'ausente'  => 'Ausente',
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" /><meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Mi historial · Asistencia QR</title>
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
      <a href="materias.php"><i class="fa-solid fa-book"></i> Mis materias</a>
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
      <div class="page-title">
        Mi historial
        <small><?= $materia_nombre ? htmlspecialchars($materia_nombre) : 'Todas las materias' ?></small>
      </div>
    </header>

    <main class="app-content">

      <!-- Stats -->
      <div class="stat-grid" style="--cols:4">
        <div class="stat-card">
          <div class="stat-icon i-blue"><i class="fa-solid fa-calendar-check"></i></div>
          <div><div class="stat-value"><?= (int)($stats['total'] ?? 0) ?></div><div class="stat-label">Clases totales</div></div>
        </div>
        <div class="stat-card">
          <div class="stat-icon i-green"><i class="fa-solid fa-user-check"></i></div>
          <div><div class="stat-value"><?= (int)($stats['presentes'] ?? 0) ?></div><div class="stat-label">Presentes</div></div>
        </div>
        <div class="stat-card">
          <div class="stat-icon i-amber"><i class="fa-solid fa-user-xmark"></i></div>
          <div><div class="stat-value"><?= (int)($stats['ausentes'] ?? 0) ?></div><div class="stat-label">Ausentes</div></div>
        </div>
        <div class="stat-card">
          <div class="stat-icon i-<?= ($stats['pct']??0) >= 75 ? 'green' : (($stats['pct']??0) >= 50 ? 'amber' : 'red') ?>">
            <i class="fa-solid fa-percent"></i>
          </div>
          <div><div class="stat-value"><?= $stats['pct'] ?? 0 ?>%</div><div class="stat-label">Asistencia</div></div>
        </div>
      </div>

      <!-- Filtros -->
      <form method="GET" action="historial.php">
        <div class="toolbar">
          <div class="filters">
            <select class="select" name="materia_id" onchange="this.form.submit()">
              <option value="">Todas las materias</option>
              <?php foreach ($mis_materias as $mm): ?>
                <option value="<?= $mm['id'] ?>" <?= $f_materia == $mm['id'] ? 'selected' : '' ?>>
                  <?= htmlspecialchars($mm['nombre']) ?>
                </option>
              <?php endforeach; ?>
            </select>
            <select class="select" name="estado" onchange="this.form.submit()">
              <option value="">Todos los estados</option>
              <option value="presente"  <?= $f_estado === 'presente'  ? 'selected' : '' ?>>Presente</option>
              <option value="tardanza"  <?= $f_estado === 'tardanza'  ? 'selected' : '' ?>>Tardanza</option>
              <option value="ausente"   <?= $f_estado === 'ausente'   ? 'selected' : '' ?>>Ausente</option>
            </select>
            <?php if ($f_materia || $f_estado): ?>
              <a href="historial.php" class="btn btn-ghost btn-sm">
                <i class="fa-solid fa-xmark"></i> Limpiar
              </a>
            <?php endif; ?>
          </div>
          <span class="badge badge-accent"><?= $total ?> registro<?= $total != 1 ? 's' : '' ?></span>
        </div>
      </form>

      <!-- Tabla -->
      <div class="card table-card">
        <div class="table-scroll">
          <table class="data-table">
            <thead>
              <tr><th>Materia</th><th>Fecha</th><th>Hora clase</th><th>Hora entrada</th><th>Estado</th></tr>
            </thead>
            <tbody>
            <?php foreach ($filas as $f): ?>
              <tr>
                <td>
                  <div class="cell-name"><?= htmlspecialchars($f['materia']) ?></div>
                  <small class="text-muted"><?= htmlspecialchars($f['curso']) ?></small>
                </td>
                <td><?= date('d/m/Y', strtotime($f['fecha'])) ?></td>
                <td><?= $f['hora'] ?></td>
                <td><?= $f['hora_entrada'] ?></td>
                <td>
                  <span class="badge <?= $badge_estado[$f['estado']] ?? 'badge-muted' ?>">
                    <?= $label_estado[$f['estado']] ?? $f['estado'] ?>
                  </span>
                </td>
              </tr>
            <?php endforeach; ?>
            <?php if (empty($filas)): ?>
              <tr><td colspan="5" style="text-align:center;padding:32px;color:var(--c-text-faint)">
                No hay registros con esos filtros.
              </td></tr>
            <?php endif; ?>
            </tbody>
          </table>
        </div>

        <?php if ($total_paginas > 1): ?>
        <div class="pagination">
          <span class="pg-info">Mostrando <?= $offset+1 ?>–<?= min($offset+$por_pag,$total) ?> de <?= $total ?></span>
          <div class="pg-controls">
            <a href="<?= url_pag(max(1,$pagina-1)) ?>" class="pg-btn <?= $pagina<=1?'disabled':'' ?>">
              <i class="fa-solid fa-chevron-left"></i>
            </a>
            <?php for($i=1;$i<=$total_paginas;$i++): ?>
              <a href="<?= url_pag($i) ?>" class="pg-btn <?= $i===$pagina?'active':'' ?>"><?= $i ?></a>
            <?php endfor; ?>
            <a href="<?= url_pag(min($total_paginas,$pagina+1)) ?>" class="pg-btn <?= $pagina>=$total_paginas?'disabled':'' ?>">
              <i class="fa-solid fa-chevron-right"></i>
            </a>
          </div>
        </div>
        <?php endif; ?>
      </div>

    </main>
  </div>
</div>
<script src="../assets/js/utils.js"></script>
</body>
</html>

