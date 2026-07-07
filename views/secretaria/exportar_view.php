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

  /* Animaciones Chips */
  @keyframes tag-in {
    from { opacity: 0; transform: scale(0.9); }
    to { opacity: 1; transform: scale(1); }
  }
  .tag-animate {
    animation: tag-in 0.2s cubic-bezier(0.34, 1.56, 0.64, 1) forwards;
  }
  @keyframes tag-out {
    from { opacity: 1; transform: scale(1); }
    to { opacity: 0; transform: scale(0.9); }
  }
  .tag-animate-out {
    animation: tag-out 0.15s ease forwards;
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
          <div class="alert alert-info" style="margin-bottom: 16px; padding: 12px; border-radius: var(--r-md); background: rgba(59, 130, 246, 0.1); border: 1px solid rgba(59, 130, 246, 0.2); color: #3b82f6; font-size: 0.85rem; display: flex; gap: 8px; align-items: center;">
            <i class="fa-solid fa-circle-info"></i>
            Esta clase aún no fue tomada. La asistencia estará disponible una vez finalizada (todos los alumnos figuran como ausentes temporalmente).
          </div>
        <?php elseif ($clase_detalle['estado'] === 'suspendida'): ?>
          <div class="alert alert-danger" style="margin-bottom: 16px; padding: 12px; border-radius: var(--r-md); background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.2); color: #ef4444; font-size: 0.85rem; display: flex; gap: 8px; align-items: center;">
            <i class="fa-solid fa-circle-exclamation"></i>
            Esta clase se encuentra suspendida (feriado o asueto). No restará asistencia a los alumnos.
          </div>
        <?php endif; ?>

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

      <h4 style="margin: 20px 0 16px 0; color: var(--c-text); font-size: 1.1rem; font-weight: 700;">Programación de Clases</h4>

      <!-- Atajos de Filtros Rápidos -->
      <div class="quick-filters" style="margin-bottom: 14px; display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
        <a href="<?= url_rango('') ?>" class="btn btn-sm <?= empty($f_rango) ? 'btn-primary' : 'btn-ghost' ?>" style="border-radius: 20px; font-size: 0.8rem; padding: 4px 12px; font-weight: 600;">Todos</a>
        <a href="<?= url_rango('hoy') ?>" class="btn btn-sm <?= $f_rango === 'hoy' ? 'btn-primary' : 'btn-ghost' ?>" style="border-radius: 20px; font-size: 0.8rem; padding: 4px 12px; font-weight: 600;">Hoy</a>
        <a href="<?= url_rango('semana') ?>" class="btn btn-sm <?= $f_rango === 'semana' ? 'btn-primary' : 'btn-ghost' ?>" style="border-radius: 20px; font-size: 0.8rem; padding: 4px 12px; font-weight: 600;">Esta semana</a>
        <a href="<?= url_rango('pendientes') ?>" class="btn btn-sm <?= $f_rango === 'pendientes' ? 'btn-primary' : 'btn-ghost' ?>" style="border-radius: 20px; font-size: 0.8rem; padding: 4px 12px; font-weight: 600;">Pendientes</a>
      </div>

      <form method="GET" action="exportar.php" id="filtrosForm">
        <input type="hidden" name="materia_id" value="<?= $materia_detalle['id'] ?>" />
        <div class="toolbar">
          <div class="filters">
            <select class="select" name="estado">
              <option value="">Todos los estados</option>
              <option value="pendiente"  <?= $f_estado==='pendiente'  ? 'selected':'' ?>>Pendiente</option>
              <option value="en_curso"   <?= $f_estado==='en_curso'   ? 'selected':'' ?>>En curso</option>
              <option value="finalizada" <?= $f_estado==='finalizada' ? 'selected':'' ?>>Finalizada</option>
            </select>
            <input class="input" type="date" name="fecha" value="<?= htmlspecialchars($f_fecha) ?>" />
            <button type="submit" class="btn btn-ghost btn-sm"><i class="fa-solid fa-filter"></i> Filtrar</button>
            <a href="exportar.php?materia_id=<?= $materia_detalle['id'] ?>" class="btn btn-ghost btn-sm" style="color: var(--c-text-soft);" title="Restablecer todos los filtros"><i class="fa-solid fa-eraser"></i> Limpiar</a>
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

      <!-- Vista Calendario -->
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
  const TODAS_LAS_CLASES = <?= json_encode($todas_las_clases_raw ?? []) ?>;
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
  let fechasSeleccionadasCalendario = [];

  const btnSuspender = document.getElementById('btnSuspenderMasivo');
  if (btnSuspender) {
    btnSuspender.addEventListener('click', () => abrirModalSuspensionMasiva([]));
  }

  const btnSuspCal = document.getElementById('btnSuspenderSeleccionadasCal');
  if (btnSuspCal) {
    btnSuspCal.addEventListener('click', function() {
      abrirModalSuspensionMasiva(fechasSeleccionadasCalendario);
    });
  }

  function updateCalendarSelectionButton() {
    const btn = document.getElementById('btnSuspenderSeleccionadasCal');
    const countSpan = document.getElementById('calSelectedCount');
    if (!btn || !countSpan) return;

    const count = fechasSeleccionadasCalendario.length;
    countSpan.textContent = count;
    if (count > 0) {
      btn.style.display = 'inline-flex';
    } else {
      btn.style.display = 'none';
    }
  }

  function abrirModalSuspensionMasiva(fechasIniciales = []) {
    let fechasSeleccionadas = [...fechasIniciales];

    const overlay = document.createElement('div');
    overlay.className = 'modal-overlay';
    overlay.innerHTML = `
      <div class="modal" style="background: var(--sidebar-bg, #062B63) !important; color: #fff !important; border: 1px solid rgba(255, 255, 255, 0.15); box-shadow: 0 10px 25px rgba(0, 0, 0, 0.35);">
        <div class="modal-head" style="border-bottom: 1px solid rgba(255, 255, 255, 0.12); padding-bottom: 12px;">
          <h3 style="color: #fff !important; font-weight: 700; font-family: Montserrat, sans-serif;">Registrar Feriados / Asuetos</h3>
          <button class="modal-close" id="cerrarModal" style="color: rgba(255,255,255,0.7) !important;">&times;</button>
        </div>
        <form id="suspenderMasivoForm" style="padding: 20px 22px 22px 22px; margin: 0;">
          <p style="font-size:0.85rem; margin-bottom: 16px; color: #e2e8f0 !important; font-weight: 500;">
            Agregá todas las fechas que desees suspender. Todas las clases de todas las materias programadas en estas fechas quedarán suspendidas y no restarán asistencia a los alumnos.
          </p>
          
          <div style="display: flex; gap: 8px; align-items: flex-end; margin-bottom: 16px;">
            <div class="field" style="flex: 1; margin: 0;">
              <label style="color: #e2e8f0 !important; font-weight: 600;">Seleccionar fecha</label>
              <input class="input" type="date" id="suspFechaInput" style="background: rgba(255,255,255,0.08) !important; color: #fff !important; border: 1px solid rgba(255,255,255,0.2) !important; color-scheme: dark;" />
            </div>
            <button type="button" class="btn btn-ghost" id="btnAgregarFecha" style="padding: 10px 14px; height: 38px; border: 1px solid rgba(255,255,255,0.25) !important; color: #fff !important;">
              <i class="fa-solid fa-plus"></i> Agregar
            </button>
          </div>
          
          <div>
            <label style="font-weight:600; font-size:0.85rem; color: #e2e8f0 !important;">Resumen de días a suspender:</label>
            <div id="listaFechasSuspendidas" style="display: flex; flex-wrap: wrap; gap: 8px; margin-top: 8px; min-height: 50px; padding: 12px; border: 1px dashed rgba(255, 255, 255, 0.25); border-radius: var(--r-md); background: rgba(0, 0, 0, 0.15);">
              <span style="font-size:0.8rem; margin: auto; color: rgba(255,255,255,0.4);" id="msgSinFechas">No hay fechas seleccionadas.</span>
            </div>
          </div>
          
          <div class="modal-foot" style="margin-top: 24px; display: flex; gap: 8px;">
            <button type="button" class="btn btn-ghost" id="cancelarSusp" style="flex:1; border: 1px solid rgba(255,255,255,0.15) !important; color: rgba(255,255,255,0.85) !important;">Cancelar</button>
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

    renderFechas();

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
          <span class="badge tag-animate" style="display: inline-flex; align-items: center; gap: 6px; padding: 6px 10px; font-size: 0.85rem; background: rgba(255, 255, 255, 0.15); color: #fff; border: 1px solid rgba(255, 255, 255, 0.25); border-radius: 4px;" data-val="${f}">
            ${formatted}
            <i class="fa-solid fa-xmark btn-remove-fecha" style="cursor:pointer; color: #ff8a8a;" data-val="${f}"></i>
          </span>
        `;
      }).join('');

      container.querySelectorAll('.btn-remove-fecha').forEach(x => {
        x.addEventListener('click', function() {
          const toRemove = this.getAttribute('data-val');
          const badge = this.closest('.badge');
          badge.classList.add('tag-animate-out');
          badge.addEventListener('animationend', function() {
            fechasSeleccionadas = fechasSeleccionadas.filter(item => item !== toRemove);
            renderFechas();
          });
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
        fechasSeleccionadasCalendario = [];
        overlay.remove();
        setTimeout(() => location.reload(), 1500);
      })
      .catch(err => App.toast(err.message, 'error'));
    };
  }

  // ── Vista Calendario e Inicialización ──
  const btnTable = document.getElementById('btnViewTable');
  const btnCalendar = document.getElementById('btnViewCalendar');
  const tableCard = document.querySelector('.card.table-card:not(#calendarCard)');
  const calendarCard = document.getElementById('calendarCard');
  
  if (btnTable && btnCalendar) {
    btnTable.addEventListener('click', function() {
      btnCalendar.classList.remove('active');
      this.classList.add('active');
      if (tableCard) tableCard.style.display = 'block';
      if (calendarCard) calendarCard.style.display = 'none';
      localStorage.setItem('secretaria_view_mode', 'table');
      fechasSeleccionadasCalendario = [];
      updateCalendarSelectionButton();
    });
    
    btnCalendar.addEventListener('click', function() {
      btnTable.classList.remove('active');
      this.classList.add('active');
      if (tableCard) tableCard.style.display = 'none';
      if (calendarCard) calendarCard.style.display = 'block';
      localStorage.setItem('secretaria_view_mode', 'calendar');
      fechasSeleccionadasCalendario = [];
      updateCalendarSelectionButton();
      renderCalendar();
    });
    
    const viewMode = localStorage.getItem('secretaria_view_mode') || 'table';
    if (viewMode === 'calendar') {
      btnCalendar.click();
    }
  }

  let currentYear = new Date().getFullYear();
  let currentMonth = new Date().getMonth();
  
  const MONTH_NAMES = [
    'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio',
    'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'
  ];

  const calPrevBtn = document.getElementById('calPrevMonth');
  const calNextBtn = document.getElementById('calNextMonth');
  if (calPrevBtn && calNextBtn) {
    calPrevBtn.onclick = function() {
      currentMonth--;
      if (currentMonth < 0) {
        currentMonth = 11;
        currentYear--;
      }
      fechasSeleccionadasCalendario = [];
      updateCalendarSelectionButton();
      renderCalendar();
    };

    calNextBtn.onclick = function() {
      currentMonth++;
      if (currentMonth > 11) {
        currentMonth = 0;
        currentYear++;
      }
      fechasSeleccionadasCalendario = [];
      updateCalendarSelectionButton();
      renderCalendar();
    };
  }

  function renderCalendar() {
    const title = document.getElementById('calMonthTitle');
    const grid = document.getElementById('calendarGrid');
    if (!title || !grid) return;

    title.textContent = `${MONTH_NAMES[currentMonth]} ${currentYear}`;
    grid.innerHTML = '';

    const firstDayIndex = new Date(currentYear, currentMonth, 1).getDay();
    const prevLastDay = new Date(currentYear, currentMonth, 0).getDate();
    const lastDay = new Date(currentYear, currentMonth + 1, 0).getDate();

    for (let i = firstDayIndex; i > 0; i--) {
      const d = prevLastDay - i + 1;
      const cell = document.createElement('div');
      cell.className = 'cal-day-cell other-month';
      cell.innerHTML = `<span class="cal-day-num">${d}</span>`;
      grid.appendChild(cell);
    }

    const today = new Date();
    for (let d = 1; d <= lastDay; d++) {
      const cell = document.createElement('div');
      const isToday = today.getDate() === d && today.getMonth() === currentMonth && today.getFullYear() === currentYear;
      const dateStr = `${currentYear}-${String(currentMonth + 1).padStart(2, '0')}-${String(d).padStart(2, '0')}`;
      const isSelected = fechasSeleccionadasCalendario.includes(dateStr);

      cell.className = `cal-day-cell${isToday ? ' today' : ''}${isSelected ? ' selected-suspension' : ''}`;
      cell.innerHTML = `<span class="cal-day-num">${d}</span>`;

      cell.addEventListener('click', function() {
        if (fechasSeleccionadasCalendario.includes(dateStr)) {
          fechasSeleccionadasCalendario = fechasSeleccionadasCalendario.filter(f => f !== dateStr);
        } else {
          fechasSeleccionadasCalendario.push(dateStr);
        }
        renderCalendar();
        updateCalendarSelectionButton();
      });

      const dayClasses = TODAS_LAS_CLASES.filter(c => c.fecha === dateStr);
      dayClasses.forEach(c => {
        const ev = document.createElement('div');
        const hora = c.hora_inicio.substring(0, 5);
        let badgeCls = 'ev-muted';
        let badgeLbl = 'Pendiente';
        if (c.estado === 'finalizada') { badgeCls = 'ev-success'; badgeLbl = 'Finalizada'; }
        else if (c.estado === 'en_curso') { badgeCls = 'ev-warning'; badgeLbl = 'En curso'; }
        else if (c.estado === 'suspendida') { badgeCls = 'ev-danger'; badgeLbl = 'Suspendida'; }

        ev.className = `cal-event ${badgeCls}`;
        ev.title = `${c.materia}\nProfesor: ${c.profesor}\nHora: ${hora}\nEstado: ${badgeLbl}\nAula: ${c.aula || 'No asignada'}`;
        ev.innerHTML = `<strong>${hora}</strong> <span>${c.modalidad === 'virtual' ? '💻' : '🚪'} ${c.aula || 'S/A'}</span>`;
        
        ev.addEventListener('click', function(e) {
          e.stopPropagation();
          abrirModalEditarClase(c);
        });
        cell.appendChild(ev);
      });

      grid.appendChild(cell);
    }

    const totalSlots = firstDayIndex + lastDay;
    const nextSlots = (totalSlots % 7 === 0) ? 0 : (7 - (totalSlots % 7));
    for (let i = 1; i <= nextSlots; i++) {
      const cell = document.createElement('div');
      cell.className = 'cal-day-cell other-month';
      cell.innerHTML = `<span class="cal-day-num">${i}</span>`;
      grid.appendChild(cell);
    }
  }
});
</script>
</body>
</html>
