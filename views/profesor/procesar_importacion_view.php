<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" /><meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Resumen de importación · Asistencia QR</title>
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="../assets/vendor/fontawesome/css/all.min.css" />
  <link rel="stylesheet" href="../assets/css/main.css" />
  <link rel="stylesheet" href="../assets/css/dashboard.css" />
  <style>
    .import-table { width:100%; border-collapse:collapse; font-size:0.87rem; }
    .import-table th {
      text-align:left; padding:9px 12px;
      background:var(--c-bg); font-weight:600;
      color:var(--c-text-soft); font-size:0.78rem;
      text-transform:uppercase; letter-spacing:.04em;
      border-bottom:2px solid var(--c-border);
    }
    .import-table td {
      padding:9px 12px; border-bottom:1px solid var(--c-border);
      vertical-align:middle;
    }
    .import-table tr:last-child td { border-bottom:none; }
    .import-table tr:hover td { background:var(--c-bg); }
    .section-title {
      display:flex; align-items:center; gap:10px;
      font-size:0.95rem; font-weight:700; margin-bottom:12px;
    }
    .count-chip {
      display:inline-flex; align-items:center; justify-content:center;
      min-width:24px; height:24px; padding:0 7px;
      border-radius:var(--r-full); font-size:0.78rem; font-weight:700;
    }
    .chip-ok      { background:var(--c-success-soft); color:var(--c-success); }
    .chip-warn    { background:var(--c-warning-soft); color:var(--c-warning); }
    .chip-danger  { background:var(--c-danger-soft);  color:var(--c-danger);  }
  </style>
</head>
<body>
<div class="app-layout role-profesor">
  <aside class="sidebar">
    <div class="sidebar-brand">
      <img src="../assets/img/logo-dashboard.png" alt="Logo" />
      <div><div class="name">Asistencia QR</div><div class="sub">Portal Profesor</div></div>
    </div>
    <nav class="sidebar-nav">
      <span class="nav-label">Principal</span>
      <a href="dashboard.php"><i class="fa-solid fa-house"></i> Mis clases</a>
      <a href="historial.php"><i class="fa-solid fa-clock-rotate-left"></i> Historial</a>
      <a href="importar_teams.php" class="active"><i class="fa-brands fa-microsoft"></i> Importar Teams</a>
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
      <div class="page-title">Resumen de importación</div>
    </header>

    <main class="app-content">

      <?php
        $clase       = $resultado['clase'];
        $importados  = $resultado['importados'];
        $advertencias= $resultado['advertencias'];
        $noMatch     = $resultado['no_matcheados'];
        $totalTeams  = count($importados) + count($advertencias) + count($noMatch);
      ?>

      <!-- ── Info de la clase ── -->
      <div class="card" style="padding:18px 24px;margin-bottom:20px;display:flex;align-items:center;gap:16px;flex-wrap:wrap">
        <div style="display:flex;align-items:center;gap:10px">
          <i class="fa-brands fa-microsoft" style="color:#0ea5e9;font-size:1.2rem"></i>
          <div>
            <div style="font-weight:700"><?= htmlspecialchars($clase['materia_nombre']) ?></div>
            <div style="color:var(--c-text-soft);font-size:0.85rem">
              <?= date('d/m/Y', strtotime($clase['fecha'])) ?>
              · <?= htmlspecialchars(substr($clase['hora_inicio'], 0, 5)) ?>
              · <?= (int)$clase['duracion_min'] ?> min
              · Clase #<?= (int)$clase['id'] ?>
            </div>
          </div>
        </div>
        <div style="margin-left:auto;display:flex;gap:10px;flex-wrap:wrap">
          <span class="count-chip chip-ok"><?= count($importados) ?></span>
          <small style="color:var(--c-text-soft)">OK</small>
          <span class="count-chip chip-warn"><?= count($advertencias) ?></span>
          <small style="color:var(--c-text-soft)">Advertencias</small>
          <span class="count-chip chip-danger"><?= count($noMatch) ?></span>
          <small style="color:var(--c-text-soft)">Sin match</small>
          <span style="color:var(--c-text-faint);font-size:0.8rem;align-self:center">
            / <?= $totalTeams ?> del archivo
          </span>
        </div>
      </div>

      <!-- ═══════════════════════════════════════════════════════════════ -->
      <!-- Sección 1: Importados OK -->
      <!-- ═══════════════════════════════════════════════════════════════ -->
      <div class="card" style="margin-bottom:20px;overflow:hidden">
        <div style="padding:18px 24px 14px">
          <div class="section-title">
            <i class="fa-solid fa-circle-check" style="color:var(--c-success)"></i>
            Importados correctamente
            <span class="count-chip chip-ok"><?= count($importados) ?></span>
          </div>
          <p style="color:var(--c-text-soft);font-size:0.85rem;margin-bottom:0">
            Match por email confirmado + inscripto en la materia. Se grabarán al confirmar.
          </p>
        </div>

        <?php if (empty($importados)): ?>
          <div class="empty-state" style="padding:32px 24px">
            <i class="fa-solid fa-inbox"></i>
            <p>Ningún participante matcheó directamente.</p>
          </div>
        <?php else: ?>
        <div style="overflow-x:auto">
          <table class="import-table">
            <thead>
              <tr>
                <th>Alumno (sistema)</th>
                <th>Legajo</th>
                <th>Nombre Teams</th>
                <th>Entrada</th>
                <th>Salida</th>
                <th>Duración</th>
                <th>Estado</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($importados as $r): ?>
              <tr>
                <td><strong><?= htmlspecialchars($r['alumno_nombre']) ?></strong></td>
                <td style="color:var(--c-text-soft)"><?= htmlspecialchars($r['alumno_legajo']) ?></td>
                <td style="color:var(--c-text-soft);font-size:0.82rem"><?= htmlspecialchars($r['nombre_teams']) ?></td>
                <td><?= htmlspecialchars($r['hora_entrada'] ?? '—') ?></td>
                <td><?= htmlspecialchars($r['hora_salida']  ?? '—') ?></td>
                <td><?= (int)$r['duracion_minutos'] ?> min</td>
                <td><?= estadoBadge($r['estado']) ?></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <?php endif; ?>
      </div>

      <!-- ═══════════════════════════════════════════════════════════════ -->
      <!-- Sección 2: Advertencias -->
      <!-- ═══════════════════════════════════════════════════════════════ -->
      <div class="card" style="margin-bottom:20px;overflow:hidden">
        <div style="padding:18px 24px 14px">
          <div class="section-title">
            <i class="fa-solid fa-triangle-exclamation" style="color:var(--c-warning)"></i>
            Advertencias — requieren revisión
            <span class="count-chip chip-warn"><?= count($advertencias) ?></span>
          </div>
          <p style="color:var(--c-text-soft);font-size:0.85rem;margin-bottom:0">
            Match por nombre (no confirmado) o alumno no inscripto en la materia.
            Se incluirán en la importación si confirmás.
          </p>
        </div>

        <?php if (empty($advertencias)): ?>
          <div class="empty-state" style="padding:32px 24px">
            <i class="fa-solid fa-circle-check" style="color:var(--c-success)"></i>
            <p>Sin advertencias.</p>
          </div>
        <?php else: ?>
        <div style="overflow-x:auto">
          <table class="import-table">
            <thead>
              <tr>
                <th>Alumno (sistema)</th>
                <th>Legajo</th>
                <th>Nombre Teams</th>
                <th>Entrada</th>
                <th>Duración</th>
                <th>Estado</th>
                <th>Advertencia</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($advertencias as $r): ?>
              <tr>
                <td><strong><?= htmlspecialchars($r['alumno_nombre']) ?></strong></td>
                <td style="color:var(--c-text-soft)"><?= htmlspecialchars($r['alumno_legajo']) ?></td>
                <td style="color:var(--c-text-soft);font-size:0.82rem"><?= htmlspecialchars($r['nombre_teams']) ?></td>
                <td><?= htmlspecialchars($r['hora_entrada'] ?? '—') ?></td>
                <td><?= (int)$r['duracion_minutos'] ?> min</td>
                <td><?= estadoBadge($r['estado']) ?></td>
                <td>
                  <span style="display:inline-flex;align-items:center;gap:5px;font-size:0.8rem;color:var(--c-warning);font-weight:600">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                    <?= htmlspecialchars($r['advertencia']) ?>
                  </span>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <?php endif; ?>
      </div>

      <!-- ═══════════════════════════════════════════════════════════════ -->
      <!-- Sección 3: Sin match -->
      <!-- ═══════════════════════════════════════════════════════════════ -->
      <div class="card" style="margin-bottom:24px;overflow:hidden">
        <div style="padding:18px 24px 14px">
          <div class="section-title">
            <i class="fa-solid fa-circle-xmark" style="color:var(--c-danger)"></i>
            Sin match — no se importarán
            <span class="count-chip chip-danger"><?= count($noMatch) ?></span>
          </div>
          <p style="color:var(--c-text-soft);font-size:0.85rem;margin-bottom:0">
            No se encontró ningún alumno con ese email ni nombre. No se grabarán al confirmar.
            <!-- La asignación manual se habilitará en el próximo paso. -->
          </p>
        </div>

        <?php if (empty($noMatch)): ?>
          <div class="empty-state" style="padding:32px 24px">
            <i class="fa-solid fa-circle-check" style="color:var(--c-success)"></i>
            <p>Todos los participantes matchearon.</p>
          </div>
        <?php else: ?>
        <div style="overflow-x:auto">
          <table class="import-table">
            <thead>
              <tr>
                <th>Nombre en Teams</th>
                <th>Email en Teams</th>
                <th>Rol Teams</th>
                <th>Duración</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($noMatch as $r): ?>
              <tr>
                <td><strong><?= htmlspecialchars($r['nombre']) ?></strong></td>
                <td style="color:var(--c-text-soft);font-size:0.82rem"><?= htmlspecialchars($r['email'] ?: '—') ?></td>
                <td style="color:var(--c-text-soft);font-size:0.82rem"><?= htmlspecialchars($r['rol_teams']) ?></td>
                <td><?= (int)$r['duracion_minutos'] ?> min</td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <?php endif; ?>
      </div>

      <!-- ── Acciones ── -->
      <div style="display:flex;gap:12px;flex-wrap:wrap;align-items:center">
        <a href="importar_teams.php" class="btn btn-ghost">
          <i class="fa-solid fa-arrow-left"></i> Volver a subir
        </a>

        <?php $total_a_grabar = count($importados) + count($advertencias); ?>
        <?php if ($total_a_grabar > 0): ?>
          <button id="btnConfirmar" class="btn btn-primary">
            <i class="fa-solid fa-check"></i>
            Confirmar importación (<?= $total_a_grabar ?> registros)
          </button>
        <?php else: ?>
          <button class="btn btn-primary" disabled>
            <i class="fa-solid fa-check"></i> Sin registros para importar
          </button>
        <?php endif; ?>
      </div>

    </main>
  </div>
</div>

<?php
function estadoBadge(string $estado): string {
    return match($estado) {
        'presente'  => '<span class="badge badge-success">Presente</span>',
        'tardanza'  => '<span class="badge badge-warning">Tardanza</span>',
        'ausente'   => '<span class="badge badge-danger">Ausente</span>',
        default     => '<span class="badge badge-muted">' . htmlspecialchars($estado) . '</span>',
    };
}
?>

<script src="../assets/js/utils.js"></script>
<script>
  const btn = document.getElementById('btnConfirmar');
  if (btn) {
    btn.addEventListener('click', function () {
      if (!confirm('¿Confirmás la importación? Se grabarán <?= count($importados) + count($advertencias) ?> registros en la base de datos.')) return;

      btn.disabled = true;
      btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Guardando...';

      App.api('../api/confirmar_importacion.php', { method: 'POST' })
        .then(function (data) {
          let msg = `Importación completada: ${data.insertados} nuevo${data.insertados !== 1 ? 's' : ''}`;
          if (data.actualizados > 0) msg += `, ${data.actualizados} actualizado${data.actualizados !== 1 ? 's' : ''}`;
          msg += '.';
          App.toast(msg, 'success');
          setTimeout(() => window.location.href = 'historial.php', 2000);
        })
        .catch(function (err) {
          App.toast(err.message, 'error');
          btn.disabled = false;
          btn.innerHTML = '<i class="fa-solid fa-check"></i> Confirmar importación';
        });
    });
  }
</script>
</body>
</html>
