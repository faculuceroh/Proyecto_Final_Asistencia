<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Panel del Profesor · Asistencia QR</title>
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="../assets/vendor/fontawesome/css/all.min.css" />
  <link rel="stylesheet" href="../assets/css/main.css" />
  <link rel="stylesheet" href="../assets/css/dashboard.css" />
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
      <a href="dashboard.php" class="active"><i class="fa-solid fa-house"></i> Mis clases</a>
      <a href="historial.php"><i class="fa-solid fa-clock-rotate-left"></i> Historial</a>
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
      <div class="page-title">
        Hola, <?= htmlspecialchars($partes[0]) ?> 👋
        <small>Estas son tus clases de hoy</small>
      </div>
      <div class="topbar-right">
        <span class="topbar-date"><i class="fa-regular fa-calendar"></i> <?= $fecha_hoy ?></span>
      </div>
    </header>

    <main class="app-content">

      <!-- Mis materias -->
      <div style="margin-bottom:32px">
        <h2 style="font-size:1.1rem;margin-bottom:4px">Mis materias</h2>
        <p class="text-muted" style="font-size:0.88rem;margin-bottom:16px">Tus materias asignadas y su horario semanal</p>

        <?php if (empty($mis_materias)): ?>
          <div class="empty-state">
            <i class="fa-solid fa-book-open"></i>
            <p>No tenés materias asignadas todavía.</p>
          </div>
        <?php else: ?>
        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:16px">
          <?php foreach ($mis_materias as $mat): ?>
          <div class="card" style="padding:20px">
            <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:8px;margin-bottom:12px">
              <div>
                <div style="font-weight:700;font-size:1rem"><?= htmlspecialchars($mat['nombre']) ?></div>
                <div class="text-muted" style="font-size:0.83rem"><?= htmlspecialchars($mat['curso']) ?></div>
              </div>
              <?php $mb = ['presencial'=>['badge-accent','Presencial'],'virtual'=>['badge-muted','Virtual'],'hibrida'=>['badge-warning','Híbrida']][$mat['modalidad']] ?? ['badge-accent', ucfirst($mat['modalidad'])]; ?>
              <span class="badge <?= $mb[0] ?>"><?= $mb[1] ?></span>
            </div>

            <!-- Horarios -->
            <?php if (!empty($mat['horarios'])): ?>
            <div style="display:flex;flex-direction:column;gap:6px;margin-bottom:12px">
              <?php foreach ($mat['horarios'] as $h): ?>
              <div style="display:flex;align-items:center;gap:8px;font-size:0.85rem">
                <span style="background:var(--c-primary);color:#fff;border-radius:6px;
                             padding:2px 8px;font-weight:700;font-size:0.75rem;flex-shrink:0">
                  <?= $nombres_dia[$h['dia_semana']] ?>
                </span>
                <span class="text-muted"><?= substr($h['hora_inicio'],0,5) ?> — <?= substr($h['hora_fin'],0,5) ?></span>
              </div>
              <?php endforeach; ?>
            </div>
            <?php else: ?>
            <p class="text-muted" style="font-size:0.83rem;margin-bottom:12px">Sin horario configurado</p>
            <?php endif; ?>

            <!-- Próxima clase -->
            <div style="border-top:1px solid var(--c-border);padding-top:10px;font-size:0.82rem">
              <?php if ($mat['proxima']): ?>
                <span style="color:var(--c-text-soft)">
                  <i class="fa-regular fa-calendar" style="margin-right:4px"></i>
                  Próxima clase: <strong><?= date('d/m/Y', strtotime($mat['proxima'])) ?></strong>
                </span>
              <?php else: ?>
                <span class="text-muted"><i class="fa-regular fa-calendar"></i> Sin clases programadas</span>
              <?php endif; ?>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
      </div>

      <!-- Clases del día -->
      <div class="page-head">
        <div>
          <h2>Clases del día</h2>
          <p>
            <?= count($clases) ?> clases programadas
            <?= $en_curso ? "· $en_curso en curso" : '' ?>
          </p>
        </div>
      </div>

      <div class="class-list">
        <?php if (empty($clases)): ?>
          <div class="empty-state">
            <i class="fa-solid fa-calendar-xmark"></i>
            <p>No tenés clases programadas para hoy.</p>
          </div>
        <?php endif; ?>

        <?php foreach ($clases as $c): ?>
          <?php
            [$badge_cls, $badge_txt] = $estado_badge[$c['estado']] ?? ['badge-muted', $c['estado']];
            $card_cls = $estado_class[$c['estado']] ?? '';
            $hora     = substr($c['hora_inicio'], 0, 5);
            $finalizada = $c['estado'] === 'finalizada';
            if ($finalizada) {
                $badge_txt = "Finalizada · {$c['presentes']}/{$c['total_alumnos']}";
            }
          ?>
          <article class="class-card <?= $card_cls ?>">
            <div class="class-time">
              <div class="hh"><?= $hora ?></div>
              <div class="dur"><?= $c['duracion_min'] ?> min</div>
            </div>
            <div class="class-info">
              <h3><?= htmlspecialchars($c['materia']) ?></h3>
              <div class="meta">
                <?php if ($c['modalidad'] === 'hibrida'): ?>
                  <span><i class="fa-solid fa-shuffle"></i> Híbrida · a elegir</span>
                <?php elseif ($c['modalidad'] === 'virtual'): ?>
                  <span><i class="fa-solid fa-video"></i> Virtual</span>
                <?php elseif ($c['aula']): ?>
                  <span><i class="fa-solid fa-location-dot"></i> <?= htmlspecialchars($c['aula']) ?></span>
                <?php else: ?>
                  <span><i class="fa-solid fa-building"></i> Presencial</span>
                <?php endif; ?>
                <span><i class="fa-solid fa-users"></i> <?= htmlspecialchars($c['curso']) ?></span>
                <span class="badge <?= $badge_cls ?>"><?= htmlspecialchars($badge_txt) ?></span>
              </div>
            </div>
            <div class="class-actions">
              <?php if ($c['modalidad'] === 'virtual'): ?>
                <a href="importar_teams.php?clase_id=<?= $c['id'] ?>" class="btn <?= $finalizada ? 'btn-ghost' : 'btn-accent' ?> btn-sm">
                  <i class="fa-brands fa-microsoft"></i> <?= $finalizada ? 'Re-importar Teams' : 'Importar Teams' ?>
                </a>
              <?php elseif ($finalizada): ?>
                <button class="btn btn-ghost btn-sm" disabled>
                  <i class="fa-solid fa-qrcode"></i> Tomar asistencia
                </button>
              <?php elseif ($c['modalidad'] === 'hibrida'): ?>
                <button class="btn btn-accent btn-sm" data-elegir-modalidad
                        data-clase-id="<?= $c['id'] ?>">
                  <i class="fa-solid fa-shuffle"></i> Elegir modalidad y dar clase
                </button>
              <?php else: ?>
                <a href="tomar_asistencia.php?clase_id=<?= $c['id'] ?>" class="btn btn-accent btn-sm">
                  <i class="fa-solid fa-qrcode"></i> Tomar asistencia
                </a>
              <?php endif; ?>
              <?php if ($c['estado'] !== 'pendiente'): ?>
                <a href="editar_asistencia.php?clase_id=<?= $c['id'] ?>" class="btn btn-ghost btn-sm">
                  <i class="fa-solid fa-pen-to-square"></i> Editar asistencia
                </a>
              <?php else: ?>
                <button class="btn btn-ghost btn-sm" disabled title="Se habilita cuando la clase arranca">
                  <i class="fa-solid fa-pen-to-square"></i> Editar asistencia
                </button>
              <?php endif; ?>
              <a href="historial.php" class="btn <?= $finalizada ? 'btn-primary' : 'btn-ghost' ?> btn-sm">
                <i class="fa-solid fa-eye"></i> Ver historial
              </a>
            </div>
          </article>
        <?php endforeach; ?>
      </div>

    </main>
  </div>
</div>

<script src="../assets/js/utils.js"></script>

<!-- Modal: elegir modalidad de una clase híbrida antes de darla -->
<div class="modal-overlay hidden" id="modalidadModal">
  <div class="modal" role="dialog" aria-modal="true" aria-labelledby="modalidadModalTitle">
    <div class="modal-head">
      <h3 id="modalidadModalTitle">¿Cómo vas a dar esta clase?</h3>
      <button class="modal-close" data-modalidad-close aria-label="Cerrar">&times;</button>
    </div>
    <div class="modal-body">
      <p class="text-muted" style="font-size:0.88rem;margin-bottom:16px">
        Esta materia es de modalidad híbrida. Elegí si hoy la vas a dar presencial (QR en el aula)
        o virtual (importás la asistencia de Teams).
      </p>
      <div style="display:flex;flex-direction:column;gap:10px">
        <button class="btn btn-accent btn-block" data-modalidad-elegir="presencial">
          <i class="fa-solid fa-location-dot"></i> Presencial (QR en el aula)
        </button>
        <button class="btn btn-accent btn-block" data-modalidad-elegir="virtual">
          <i class="fa-solid fa-video"></i> Virtual (importar Teams)
        </button>
      </div>
    </div>
    <div class="modal-foot">
      <button class="btn btn-ghost" data-modalidad-close>Cancelar</button>
    </div>
  </div>
</div>
<script>
  (function () {
    const modal = App.qs('#modalidadModal');
    if (!modal) return;
    let claseId = null;

    function open(id) {
      claseId = id;
      modal.classList.remove('hidden');
    }
    function close() {
      claseId = null;
      modal.classList.add('hidden');
    }

    App.qsa('[data-elegir-modalidad]').forEach(b =>
      b.addEventListener('click', () => open(b.dataset.claseId))
    );
    App.qsa('[data-modalidad-close]').forEach(b => b.addEventListener('click', close));
    modal.addEventListener('click', e => { if (e.target === modal) close(); });

    App.qsa('[data-modalidad-elegir]').forEach(btn => {
      btn.addEventListener('click', () => {
        const modalidad = btn.dataset.modalidadElegir;
        App.api('../api/elegir_modalidad_clase.php', {
          method: 'POST', loader: true,
          body: JSON.stringify({ clase_id: parseInt(claseId), modalidad }),
        })
        .then(() => {
          window.location.href = modalidad === 'virtual'
            ? 'importar_teams.php?clase_id=' + claseId
            : 'tomar_asistencia.php?clase_id=' + claseId;
        })
        .catch(err => App.toast(err.message, 'error'));
      });
    });
  })();
</script>
</body>
</html>
