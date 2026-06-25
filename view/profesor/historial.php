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
