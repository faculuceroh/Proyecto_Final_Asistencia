<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" /><meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Clases del período · Secretaría</title>
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="../assets/vendor/fontawesome/css/all.min.css" />
  <link rel="stylesheet" href="../assets/css/main.css" />
  <link rel="stylesheet" href="../assets/css/dashboard.css" />
  <style>
  .btn-volver {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: var(--c-surface, #fff);
    border: 2px solid var(--c-primary) !important;
    color: var(--c-primary) !important;
    padding: 8px 16px !important;
    border-radius: var(--r-md) !important;
    font-weight: 700 !important;
    font-size: 0.85rem !important;
    text-decoration: none;
    transition: all var(--t-fast) ease-in-out;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
  }
  .btn-volver:hover {
    background: var(--c-primary) !important;
    color: #fff !important;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    transform: translateY(-1px);
  }
  .btn-volver:active {
    transform: translateY(1px);
  }
  </style>
</head>
<body>
<div class="app-layout role-secretaria">
  <aside class="sidebar">
    <div class="sidebar-brand">
      <img src="../assets/img/logo-dashboard.png" alt="Logo" />
      <div><div class="name">Asistencia QR</div><div class="sub">Secretaría</div></div>
    </div>
    <nav class="sidebar-nav">
      <span class="nav-label">Gestión</span>
      <a href="exportar.php" class="active"><i class="fa-solid fa-file-export"></i> Clases del período</a>
      <a href="materias.php"><i class="fa-solid fa-book"></i> Materias</a>
      <a href="aulas.php"><i class="fa-solid fa-door-open"></i> Aulas</a>
      <a href="inscripciones.php"><i class="fa-solid fa-user-plus"></i> Inscripciones</a>
      <a href="usuarios.php"><i class="fa-solid fa-users"></i> Alta de usuarios</a>
      <a href="reportes.php"><i class="fa-solid fa-chart-pie"></i> Reportes</a>
      <span class="nav-label">Cuenta</span>
      <a href="perfil.php"><i class="fa-solid fa-user"></i> Mi perfil</a>
      <a href="../logout.php"><i class="fa-solid fa-right-from-bracket"></i> Cerrar sesión</a>
    </nav>
    <div class="sidebar-user">
      <?php if (!empty($_SESSION['foto'])): ?><img class="avatar" src="../assets/uploads/perfiles/<?= htmlspecialchars($_SESSION['foto']) ?>" alt="Foto de perfil" /><?php else: ?><div class="avatar"><?= htmlspecialchars($iniciales) ?></div><?php endif; ?>
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
      <?php if ($clase_detalle): ?>
        <div class="page-title">
          <?= htmlspecialchars($clase_detalle['materia']) ?>
          <small><?= date('d/m/Y', strtotime($clase_detalle['fecha'])) ?> · <?= substr($clase_detalle['hora_inicio'],0,5) ?></small>
        </div>
        <div class="topbar-right">
          <button class="btn btn-success btn-sm"
                  data-export-url="../api/exportar.php?clase_id=<?= $clase_id ?>">
            <i class="fa-solid fa-file-excel"></i> Exportar Excel
          </button>
        </div>
      <?php elseif ($materia_detalle): ?>
        <div class="page-title">
          <?= htmlspecialchars($materia_detalle['nombre']) ?>
          <small><?= htmlspecialchars($materia_detalle['curso']) ?> · Clases del período</small>
        </div>
        <!-- Botón volver removido de cabecera -->
      <?php else: ?>
        <div class="page-title">Clases del período <small>Exportá y enviá los registros de asistencia</small></div>
        <div class="topbar-right">
          <button class="btn btn-accent btn-sm" id="btnSuspenderMasivo" title="Suspender clases masivamente">
            <i class="fa-solid fa-calendar-minus"></i> Registrar feriado / asueto
          </button>
        </div>
      <?php endif; ?>
    </header>
    <main class="app-content">

      <?php if ($clase_detalle): ?>
        <!-- ── VISTA DETALLE DE CLASE ─────────────────────────── -->
        <div style="display: flex; justify-content: flex-end; margin-bottom: 16px;">
          <a href="exportar.php?materia_id=<?= $clase_detalle['materia_id'] ?>" class="btn-volver">
            <i class="fa-solid fa-arrow-left"></i> Volver a la lista de clases
          </a>
        </div>

        <?php
          $pres_count = count(array_filter($alumnos_clase, fn($a) => in_array($a['estado'], ['presente','tardanza'])));
          $aus_count  = count(array_filter($alumnos_clase, fn($a) => $a['estado'] === 'ausente'));
          $tot_count  = count($alumnos_clase);
          $pct_cl     = $tot_count ? round($pres_count / $tot_count * 100, 1) : 0;
          $badge_est_cls = ['pendiente'=>['badge-muted','Pendiente'],'en_curso'=>['badge-warning','En curso'],'finalizada'=>['badge-success','Finalizada']];
          [$est_cls, $est_lbl] = $badge_est_cls[$clase_detalle['estado']] ?? ['badge-muted', $clase_detalle['estado']];
        ?>
        <div class="stat-grid" style="margin-bottom:24px">
          <div class="stat-card">
            <div class="stat-icon i-navy"><i class="fa-solid fa-users"></i></div>
            <div><div class="stat-value"><?= $total_alumnos ?></div><div class="stat-label">Inscriptos</div></div>
          </div>
          <div class="stat-card">
            <div class="stat-icon i-green"><i class="fa-solid fa-user-check"></i></div>
            <div><div class="stat-value"><?= $pres_count ?></div><div class="stat-label">Presentes (en pág)</div></div>
          </div>
          <div class="stat-card">
            <div class="stat-icon i-amber"><i class="fa-solid fa-user-xmark"></i></div>
            <div><div class="stat-value"><?= $aus_count ?></div><div class="stat-label">Ausentes (en pág)</div></div>
          </div>
          <div class="stat-card">
            <div class="stat-icon i-blue"><i class="fa-solid fa-percent"></i></div>
            <div><div class="stat-value"><?= $pct_cl ?>%</div><div class="stat-label">Asistencia</div></div>
          </div>
        </div>

        <div style="margin-bottom:12px;display:flex;align-items:center;gap:10px;font-size:0.9rem;color:var(--c-text-soft)">
          <i class="fa-solid fa-chalkboard-user"></i> Prof. <?= htmlspecialchars($clase_detalle['profesor']) ?>
          &nbsp;·&nbsp;
          <i class="fa-solid fa-users"></i> <?= htmlspecialchars($clase_detalle['curso']) ?>
          &nbsp;·&nbsp;
          <span class="badge <?= $est_cls ?>"><?= $est_lbl ?></span>
        </div>

        <?php if ($clase_detalle['estado'] === 'pendiente'): ?>
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
              <?php foreach ($alumnos_clase as $a): ?>
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
              <?php if (empty($alumnos_clase)): ?>
                <tr><td colspan="4" style="text-align:center;padding:32px;color:var(--c-text-faint)">
                  No hay alumnos inscriptos en esta materia.
                </td></tr>
              <?php endif; ?>
              </tbody>
            </table>
          </div>
          <?php if (!empty($alumnos_clase) && $total_paginas_alumnos > 1): ?>
          <div class="pagination" style="padding: 12px 16px; border-top: 1px solid var(--c-border);">
            <span class="pg-info">Mostrando <?= $offset_alumnos+1 ?>–<?= min($offset_alumnos+$por_pag_alumnos,$total_alumnos) ?> de <?= $total_alumnos ?> alumnos</span>
            <div class="pg-controls">
              <?php
              function url_pag_alumnos(int $p): string {
                  $params = $_GET; $params['pagina_alumnos'] = $p;
                  return '?' . http_build_query($params);
              }
              ?>
              <a href="<?= url_pag_alumnos(max(1,$pagina_alumnos-1)) ?>" class="pg-btn <?= $pagina_alumnos<=1?'disabled':'' ?>"><i class="fa-solid fa-chevron-left"></i></a>
              <?php
              $rango_al = get_page_range($pagina_alumnos, $total_paginas_alumnos);
              foreach ($rango_al as $p):
                  if ($p === '…'):
              ?>
                  <span class="pg-btn" style="cursor: default; border-color: transparent;">…</span>
              <?php else: ?>
                  <a href="<?= url_pag_alumnos($p) ?>" class="pg-btn <?= $p === $pagina_alumnos ? 'active' : '' ?>"><?= $p ?></a>
              <?php
                  endif;
              endforeach;
              ?>
              <a href="<?= url_pag_alumnos(min($total_paginas_alumnos,$pagina_alumnos+1)) ?>" class="pg-btn <?= $pagina_alumnos>=$total_paginas_alumnos?'disabled':'' ?>"><i class="fa-solid fa-chevron-right"></i></a>
            </div>
          </div>
          <?php endif; ?>
        </div>
        <?php endif; ?>

      <?php elseif ($materia_detalle): ?>
      <!-- ── VISTA LISTA DE CLASES DE LA MATERIA ────────────── -->
      <div style="display: flex; justify-content: flex-end; margin-bottom: 16px;">
        <a href="exportar.php" class="btn-volver">
          <i class="fa-solid fa-arrow-left"></i> Volver a la lista de materias
        </a>
      </div>

      <div class="stat-grid">
        <div class="stat-card"><div class="stat-icon i-navy"><i class="fa-solid fa-book"></i></div><div><div class="stat-value"><?= $total_clases ?></div><div class="stat-label">Clases del período</div></div></div>
        <div class="stat-card"><div class="stat-icon i-green"><i class="fa-solid fa-user-check"></i></div><div><div class="stat-value"><?= number_format($total_pres) ?></div><div class="stat-label">Presentes</div></div></div>
        <div class="stat-card"><div class="stat-icon i-amber"><i class="fa-solid fa-user-xmark"></i></div><div><div class="stat-value"><?= number_format($total_aus) ?></div><div class="stat-label">Ausentes</div></div></div>
        <div class="stat-card"><div class="stat-icon i-blue"><i class="fa-solid fa-percent"></i></div><div><div class="stat-value"><?= $prom_asist ?>%</div><div class="stat-label">Asistencia promedio</div></div></div>
      </div>

      <form method="GET" action="exportar.php" id="filtrosForm">
        <input type="hidden" name="materia_id" value="<?= $materia_detalle['id'] ?>" />
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
            <select class="select" name="estado">
              <option value="">Todos los estados</option>
              <option value="pendiente"  <?= $f_estado==='pendiente'  ? 'selected':'' ?>>Pendiente</option>
              <option value="en_curso"   <?= $f_estado==='en_curso'   ? 'selected':'' ?>>En curso</option>
              <option value="finalizada" <?= $f_estado==='finalizada' ? 'selected':'' ?>>Finalizada</option>
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
              <tr><th>Materia</th><th>Profesor</th><th>Fecha</th><th>Estado</th><th>Presentes</th><th>Ausentes</th><th>Asistencia</th><th>Acciones</th></tr>
            </thead>
            <tbody>
            <?php
              $badge_estado = [
                'pendiente'  => ['badge-muted',   'Pendiente'],
                'en_curso'   => ['badge-warning',  'En curso'],
                'finalizada' => ['badge-success',  'Finalizada'],
                'suspendida' => ['badge-danger',   'Suspendida'],
              ];
            ?>
            <?php foreach ($clases as $c): ?>
              <?php
                $pct    = (float)($c['pct'] ?? 0);
                [$bc, $bl] = $badge_estado[$c['estado']] ?? ['badge-muted', $c['estado']];
                $finalizada = $c['estado'] === 'finalizada';
              ?>
              <tr>
                <td>
                  <div class="cell-name"><?= htmlspecialchars($c['materia']) ?></div>
                  <small class="text-muted"><?= htmlspecialchars($c['curso']) ?></small>
                </td>
                <td><?= htmlspecialchars($c['profesor'] ?? '—') ?></td>
                <td>
                  <?= date('d/m/Y', strtotime($c['fecha'])) ?>
                  <br><small class="text-muted"><?= substr($c['hora_inicio'], 0, 5) ?></small>
                </td>
                <td><span class="badge <?= $bc ?>"><?= $bl ?></span></td>
                <td><?= $finalizada ? '<span class="badge badge-success">'.(int)$c['presentes'].'</span>' : '—' ?></td>
                <td><?= $finalizada ? '<span class="badge badge-danger">'.(int)$c['ausentes'].'</span>'   : '—' ?></td>
                <td>
                  <?php if ($finalizada): ?>
                  <div style="display:flex;align-items:center;gap:8px">
                    <div class="progress" style="flex:1">
                      <span style="width:<?= $pct ?>%;background:<?= $pct>=75?'var(--c-success)':($pct>=50?'var(--c-warning)':'var(--c-danger)') ?>"></span>
                    </div>
                    <?= $pct ?>%
                  </div>
                  <?php else: ?>—<?php endif; ?>
                </td>
                <td>
                  <div style="display:flex;gap:6px;flex-wrap:wrap">
                    <a href="exportar.php?clase_id=<?= $c['id'] ?>" class="btn btn-ghost btn-sm" title="Ver alumnos">
                      <i class="fa-solid fa-users"></i> Ver
                    </a>
                    <button class="btn btn-ghost btn-sm btn-edit-clase"
                            data-clase="<?= htmlspecialchars(json_encode($c)) ?>"
                            title="Editar clase">
                      <i class="fa-solid fa-pen"></i>
                    </button>
                    <button class="btn btn-ghost btn-sm btn-elim-clase"
                            data-clase-id="<?= $c['id'] ?>"
                            data-fecha="<?= date('d/m/Y', strtotime($c['fecha'])) ?>"
                            title="Eliminar clase" style="color:var(--c-danger)">
                      <i class="fa-solid fa-trash"></i>
                    </button>
                    <?php if ($finalizada): ?>
                    <button class="btn btn-success btn-sm"
                            data-export-url="../api/exportar.php?clase_id=<?= $c['id'] ?>" title="Exportar Excel">
                      <i class="fa-solid fa-file-excel"></i> Excel
                    </button>
                    <button class="btn btn-ghost btn-sm"
                            data-send-url="../api/enviar_secretaria.php"
                            data-clase-id="<?= $c['id'] ?>" title="Enviar reporte">
                      <i class="fa-solid fa-paper-plane"></i> Enviar
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
        <div class="pagination">
          <span class="pg-info">Mostrando <?= $offset+1 ?>–<?= min($offset+$por_pag,$total) ?> de <?= $total ?></span>
          <div class="pg-controls">
            <a href="<?= url_pag(max(1,$pagina-1)) ?>" class="pg-btn <?= $pagina<=1?'disabled':'' ?>"><i class="fa-solid fa-chevron-left"></i></a>
            <?php
            $rango = get_page_range($pagina, $total_paginas);
            foreach ($rango as $p):
                if ($p === '…'):
            ?>
                <span class="pg-btn" style="cursor: default; border-color: transparent;">…</span>
            <?php else: ?>
                <a href="<?= url_pag($p) ?>" class="pg-btn <?= $p === $pagina ? 'active' : '' ?>"><?= $p ?></a>
            <?php
                endif;
            endforeach;
            ?>
            <a href="<?= url_pag(min($total_paginas,$pagina+1)) ?>" class="pg-btn <?= $pagina>=$total_paginas?'disabled':'' ?>"><i class="fa-solid fa-chevron-right"></i></a>
          </div>
        </div>
      </div>

      <?php else: ?>
      <!-- ── VISTA LISTA DE MATERIAS (POR DEFECTO) ──────────── -->
      <div class="stat-grid">
        <div class="stat-card"><div class="stat-icon i-navy"><i class="fa-solid fa-book"></i></div><div><div class="stat-value"><?= $total_clases ?></div><div class="stat-label">Clases del período</div></div></div>
        <div class="stat-card"><div class="stat-icon i-green"><i class="fa-solid fa-user-check"></i></div><div><div class="stat-value"><?= number_format($total_pres) ?></div><div class="stat-label">Presentes</div></div></div>
        <div class="stat-card"><div class="stat-icon i-amber"><i class="fa-solid fa-user-xmark"></i></div><div><div class="stat-value"><?= number_format($total_aus) ?></div><div class="stat-label">Ausentes</div></div></div>
        <div class="stat-card"><div class="stat-icon i-blue"><i class="fa-solid fa-percent"></i></div><div><div class="stat-value"><?= $prom_asist ?>%</div><div class="stat-label">Asistencia promedio</div></div></div>
      </div>

      <!-- Buscador de Materias -->
      <div class="toolbar" style="margin-bottom: 16px;">
        <div style="position:relative; width: 100%; max-width: 320px;">
          <i class="fa-solid fa-magnifying-glass" style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:var(--c-text-faint);font-size:0.85rem;pointer-events:none"></i>
          <input id="searchMateriaInput" class="input" placeholder="Buscar materia o curso..."
                 style="padding-left:34px;height:38px;font-size:0.9rem;width:100%" />
        </div>
      </div>

      <div class="card table-card">
        <div class="table-scroll">
          <table class="data-table">
            <thead>
              <tr>
                <th>Materia</th>
                <th>Profesor</th>
                <th>Modalidad</th>
                <th style="text-align:center">Clases Totales</th>
                <th style="text-align:center">Finalizadas</th>
                <th style="text-align:center">Restantes</th>
                <th style="text-align:right">Acciones</th>
              </tr>
            </thead>
            <tbody id="materiasTableBody">
              <?php foreach ($materias_con_stats as $m): ?>
                <?php
                  $mb = ['presencial'=>['badge-accent','Presencial'],'virtual'=>['badge-muted','Virtual'],'hibrida'=>['badge-warning','Híbrida']][$m['modalidad']] ?? ['badge-accent', ucfirst($m['modalidad'])];
                  $p2 = $m['profesor_2'] ? '<br><small class="text-muted">'.htmlspecialchars($m['profesor_2']).'</small>' : '';
                  $cod = $m['codigo'] ? '<small class="text-muted">'.htmlspecialchars($m['codigo']).' · </small>' : '';
                ?>
                <tr class="materia-row" data-search-text="<?= htmlspecialchars(strtolower($m['nombre'] . ' ' . $m['codigo'] . ' ' . $m['curso'] . ' ' . $m['profesor'])) ?>">
                  <td>
                    <div class="cell-name"><?= htmlspecialchars($m['nombre']) ?></div>
                    <small class="text-muted"><?= $cod ?><?= htmlspecialchars($m['curso']) ?></small>
                  </td>
                  <td><?= htmlspecialchars($m['profesor']) ?><?= $p2 ?></td>
                  <td><span class="badge <?= $mb[0] ?>"><?= $mb[1] ?></span></td>
                  <td style="text-align:center; font-weight: 600;"><?= $m['total_clases'] ?></td>
                  <td style="text-align:center">
                    <?php if ($m['clases_finalizadas'] > 0): ?>
                      <span class="badge badge-success"><?= $m['clases_finalizadas'] ?></span>
                    <?php else: ?>
                      <span class="text-muted">—</span>
                    <?php endif; ?>
                  </td>
                  <td style="text-align:center">
                    <?php
                      $pendientes = $m['clases_pendientes'] + $m['clases_en_curso'];
                      if ($pendientes > 0):
                    ?>
                      <span class="badge badge-warning"><?= $pendientes ?></span>
                    <?php else: ?>
                      <span class="text-muted">—</span>
                    <?php endif; ?>
                  </td>
                  <td style="text-align:right">
                    <a href="exportar.php?materia_id=<?= $m['id'] ?>" class="btn btn-ghost btn-sm" style="color: var(--c-primary); font-weight: 600;">
                      <i class="fa-solid fa-folder-open"></i> Ver clases
                    </a>
                  </td>
                </tr>
              <?php endforeach; ?>
              <?php if (empty($materias_con_stats)): ?>
                <tr>
                  <td colspan="7" style="text-align:center;padding:32px;color:var(--c-text-faint)">
                    No hay materias cargadas.
                  </td>
                </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>

      <?php endif; ?>

    </main>
  </div>
</div>
<script src="../assets/js/utils.js"></script>
<script src="../assets/js/export.js"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
  const searchInput = document.getElementById('searchMateriaInput');
  if (searchInput) {
    searchInput.addEventListener('input', function() {
      const q = this.value.toLowerCase().trim();
      const rows = document.querySelectorAll('.materia-row');
      rows.forEach(row => {
        const text = row.getAttribute('data-search-text') || '';
        if (text.includes(q)) {
          row.style.display = '';
        } else {
          row.style.display = 'none';
        }
      });
    });
  }

  // ── Editar Clase ──────────────────────────────────────────────
  const editBtns = document.querySelectorAll('.btn-edit-clase');
  editBtns.forEach(btn => {
    btn.addEventListener('click', function() {
      const c = JSON.parse(this.getAttribute('data-clase'));
      abrirModalEditarClase(c);
    });
  });

  // ── Eliminar Clase ────────────────────────────────────────────
  const elimBtns = document.querySelectorAll('.btn-elim-clase');
  elimBtns.forEach(btn => {
    btn.addEventListener('click', function() {
      const id = this.getAttribute('data-clase-id');
      const fecha = this.getAttribute('data-fecha');
      confirmarEliminarClase(id, fecha);
    });
  });

  function abrirModalEditarClase(c) {
    const overlay = document.createElement('div');
    overlay.className = 'modal-overlay';
    overlay.innerHTML = `
      <div class="modal">
        <div class="modal-head">
          <h3>Editar clase individual</h3>
          <button class="modal-close" id="cerrarModal">&times;</button>
        </div>
        <form id="editClaseForm" style="margin-top: 16px;">
          <input type="hidden" name="clase_id" value="${c.id}" />
          
          <div class="field">
            <label>Fecha</label>
            <input class="input" type="date" name="fecha" value="${c.fecha}" required />
          </div>
          
          <div class="field">
            <label>Hora de inicio</label>
            <input class="input" type="time" name="hora_inicio" value="${c.hora_inicio.substring(0,5)}" required />
          </div>
          
          <div class="field">
            <label>Duración (minutos)</label>
            <input class="input" type="number" name="duracion_min" value="${c.duracion_min || 90}" min="1" required />
          </div>
          
          <div class="field">
            <label>Modalidad</label>
            <select class="select" name="modalidad" id="editModalidad" required>
              <option value="presencial" ${c.modalidad === 'presencial' ? 'selected' : ''}>Presencial</option>
              <option value="virtual" ${c.modalidad === 'virtual' ? 'selected' : ''}>Virtual</option>
              <option value="hibrida" ${c.modalidad === 'hibrida' ? 'selected' : ''}>Híbrida</option>
            </select>
          </div>
          
          <div class="field" id="aulaField" style="display: ${c.modalidad === 'virtual' ? 'none' : 'block'};">
            <label>Aula</label>
            <input class="input" type="text" name="aula" value="${c.aula || ''}" placeholder="Ej: Aula 102" />
          </div>
          
          <div class="field">
            <label>Estado</label>
            <select class="select" name="estado" required>
              <option value="pendiente" ${c.estado === 'pendiente' ? 'selected' : ''}>Pendiente</option>
              <option value="en_curso" ${c.estado === 'en_curso' ? 'selected' : ''}>En curso</option>
              <option value="finalizada" ${c.estado === 'finalizada' ? 'selected' : ''}>Finalizada</option>
              <option value="suspendida" ${c.estado === 'suspendida' ? 'selected' : ''}>Suspendida</option>
            </select>
          </div>
          
          <div class="modal-foot" style="margin-top: 24px; display: flex; gap: 8px;">
            <button type="button" class="btn btn-ghost" id="cancelarEdit" style="flex:1">Cancelar</button>
            <button type="submit" class="btn btn-primary" style="flex:1">Guardar cambios</button>
          </div>
        </form>
      </div>`;
    document.body.appendChild(overlay);
    
    const modSelect = overlay.querySelector('#editModalidad');
    const aulaDiv = overlay.querySelector('#aulaField');
    modSelect.addEventListener('change', function() {
      if (this.value === 'virtual') {
        aulaDiv.style.display = 'none';
      } else {
        aulaDiv.style.display = 'block';
      }
    });

    overlay.querySelector('#cerrarModal').onclick =
    overlay.querySelector('#cancelarEdit').onclick = () => overlay.remove();

    overlay.querySelector('#editClaseForm').onsubmit = function(e) {
      e.preventDefault();
      const fd = new FormData(this);
      const payload = {
        clase_id:     parseInt(fd.get('clase_id')),
        fecha:        fd.get('fecha'),
        hora_inicio:  fd.get('hora_inicio'),
        duracion_min: parseInt(fd.get('duracion_min')),
        aula:         fd.get('aula') || '',
        modalidad:     fd.get('modalidad'),
        estado:       fd.get('estado')
      };

      App.api('../api/editar_clase.php', {
        method: 'POST', loader: true,
        body: JSON.stringify(payload)
      })
      .then(res => {
        App.toast('Clase actualizada con éxito.', 'success');
        overlay.remove();
        location.reload();
      })
      .catch(err => App.toast(err.message, 'error'));
    };
  }

  function confirmarEliminarClase(id, fecha) {
    const overlay = document.createElement('div');
    overlay.className = 'modal-overlay';
    overlay.innerHTML = `
      <div class="modal">
        <div class="modal-head">
          <h3>Eliminar clase</h3>
          <button class="modal-close" id="cerrarModal">&times;</button>
        </div>
        <div class="modal-body" style="margin-top: 16px;">
          <p>¿Estás seguro que querés eliminar la clase del día <strong>${fecha}</strong>?</p>
          <p class="text-muted" style="font-size:0.85rem;margin-top:8px">
            Esta acción borrará la clase y todas las asistencias asociadas de forma permanente.
          </p>
        </div>
        <div class="modal-foot" style="margin-top: 24px; display: flex; gap: 8px;">
          <button class="btn btn-ghost" id="cancelarElim" style="flex:1">Cancelar</button>
          <button class="btn btn-danger" id="confirmarElim" style="flex:1">
            <i class="fa-solid fa-trash"></i> Sí, eliminar
          </button>
        </div>
      </div>`;
    document.body.appendChild(overlay);

    overlay.querySelector('#cerrarModal').onclick =
    overlay.querySelector('#cancelarElim').onclick = () => overlay.remove();

    overlay.querySelector('#confirmarElim').onclick = function() {
      overlay.remove();
      App.api('../api/eliminar_clase.php', {
        method: 'POST', loader: true,
        body: JSON.stringify({ clase_id: parseInt(id) })
      })
      .then(() => {
        App.toast('Clase eliminada.', 'success');
        location.reload();
      })
      .catch(err => App.toast(err.message, 'error'));
    };
  }

  // ── Registrar Feriado / Suspensión Masiva ─────────────────────
  const btnSuspender = document.getElementById('btnSuspenderMasivo');
  if (btnSuspender) {
    btnSuspender.addEventListener('click', abrirModalSuspensionMasiva);
  }

  function abrirModalSuspensionMasiva() {
    let fechasSeleccionadas = [];

    const overlay = document.createElement('div');
    overlay.className = 'modal-overlay';
    overlay.innerHTML = `
      <div class="modal">
        <div class="modal-head">
          <h3>Registrar Feriados / Asuetos</h3>
          <button class="modal-close" id="cerrarModal">&times;</button>
        </div>
        <form id="suspenderMasivoForm" style="margin-top: 16px;">
          <p class="text-muted" style="font-size:0.85rem; margin-bottom: 16px;">
            Agregá todas las fechas que desees suspender. Todas las clases de todas las materias programadas en estas fechas quedarán suspendidas y no restarán asistencia a los alumnos.
          </p>
          
          <div style="display: flex; gap: 8px; align-items: flex-end; margin-bottom: 16px;">
            <div class="field" style="flex: 1; margin: 0;">
              <label>Seleccionar fecha</label>
              <input class="input" type="date" id="suspFechaInput" />
            </div>
            <button type="button" class="btn btn-ghost" id="btnAgregarFecha" style="padding: 10px 14px; height: 38px;">
              <i class="fa-solid fa-plus"></i> Agregar
            </button>
          </div>
          
          <div>
            <label style="font-weight:600; font-size:0.85rem; color:var(--c-text-soft);">Resumen de días a suspender:</label>
            <div id="listaFechasSuspendidas" style="display: flex; flex-wrap: wrap; gap: 8px; margin-top: 8px; min-height: 50px; padding: 12px; border: 1px dashed var(--c-border); border-radius: var(--r-md); background: var(--c-muted-soft);">
              <span class="text-muted" style="font-size:0.8rem; margin: auto;" id="msgSinFechas">No hay fechas seleccionadas.</span>
            </div>
          </div>
          
          <div class="modal-foot" style="margin-top: 24px; display: flex; gap: 8px;">
            <button type="button" class="btn btn-ghost" id="cancelarSusp" style="flex:1">Cancelar</button>
            <button type="submit" class="btn btn-danger" style="flex:1">
              <i class="fa-solid fa-calendar-minus"></i> Confirmar suspensión
            </button>
          </div>
        </form>
      </div>`;
    document.body.appendChild(overlay);

    const inputFecha = overlay.querySelector('#suspFechaInput');
    const btnAgregar = overlay.querySelector('#btnAgregarFecha');
    const container = overlay.querySelector('#listaFechasSuspendidas');
    const msgSinFechas = overlay.querySelector('#msgSinFechas');

    btnAgregar.addEventListener('click', function() {
      const val = inputFecha.value;
      if (!val) return;

      if (fechasSeleccionadas.includes(val)) {
        App.toast('Esta fecha ya fue agregada.', 'warning');
        return;
      }

      fechasSeleccionadas.push(val);
      renderFechas();
      inputFecha.value = '';
    });

    function renderFechas() {
      fechasSeleccionadas.sort();

      if (fechasSeleccionadas.length === 0) {
        container.innerHTML = '';
        container.appendChild(msgSinFechas);
        return;
      }

      if (msgSinFechas.parentNode) {
        msgSinFechas.remove();
      }

      container.innerHTML = fechasSeleccionadas.map(f => {
        const [y, m, d] = f.split('-');
        const formatted = `${d}/${m}/${y}`;
        return `
          <span class="badge badge-muted" style="display: inline-flex; align-items: center; gap: 6px; padding: 6px 10px; font-size: 0.85rem;" data-val="${f}">
            ${formatted}
            <i class="fa-solid fa-xmark text-danger btn-remove-fecha" style="cursor:pointer;" data-val="${f}"></i>
          </span>
        `;
      }).join('');

      container.querySelectorAll('.btn-remove-fecha').forEach(x => {
        x.addEventListener('click', function() {
          const toRemove = this.getAttribute('data-val');
          fechasSeleccionadas = fechasSeleccionadas.filter(item => item !== toRemove);
          renderFechas();
        });
      });
    }

    overlay.querySelector('#cerrarModal').onclick =
    overlay.querySelector('#cancelarSusp').onclick = () => overlay.remove();

    overlay.querySelector('#suspenderMasivoForm').onsubmit = function(e) {
      e.preventDefault();
      if (fechasSeleccionadas.length === 0) {
        App.toast('Debe agregar al menos una fecha.', 'error');
        return;
      }

      App.api('../api/suspender_clases_masivo.php', {
        method: 'POST', loader: true,
        body: JSON.stringify({ fechas: fechasSeleccionadas })
      })
      .then(res => {
        App.toast('Clases suspendidas con éxito. Afectadas: ' + res.afectadas + ' clases.', 'success');
        overlay.remove();
        setTimeout(() => location.reload(), 1500);
      })
      .catch(err => App.toast(err.message, 'error'));
    };
  }
});
</script>
</body>
</html>
