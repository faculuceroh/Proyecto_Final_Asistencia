<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" /><meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Aulas · Secretaría</title>
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
  <link rel="stylesheet" href="../assets/css/main.css" />
  <link rel="stylesheet" href="../assets/css/dashboard.css" />
  <style>
    .aula-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(280px,1fr)); gap:20px; }
    .aula-card { padding:20px; }
    .qr-box { border:1px solid var(--c-border); border-radius:12px; padding:16px; display:flex; flex-direction:column; align-items:center; gap:10px; margin:16px 0; }
    .qr-box canvas { border-radius:6px; }
    .token-text { font-size:0.7rem; color:var(--c-text-faint); word-break:break-all; font-family:monospace; }
  </style>
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
      <a href="exportar.php"><i class="fa-solid fa-file-export"></i> Clases del período</a>
      <a href="materias.php"><i class="fa-solid fa-book"></i> Materias</a>
      <a href="aulas.php" class="active"><i class="fa-solid fa-door-open"></i> Aulas</a>
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
      <div class="page-title">Aulas <small>QR fijos por aula</small></div>
    </header>

    <main class="app-content">

      <!-- Formulario nueva aula -->
      <div class="card" style="padding:24px;margin-bottom:28px">
        <h3 style="font-size:1rem;margin-bottom:12px"><i class="fa-solid fa-plus"></i> Nueva aula</h3>
        <div style="display:flex;gap:12px;align-items:flex-end">
          <div class="field" style="flex:1;margin:0">
            <label>Nombre del aula</label>
            <input class="input" id="nombreAula" placeholder="Ej: Aula 201, Laboratorio de Informática..." />
          </div>
          <button class="btn btn-primary" id="btnCrearAula">
            <i class="fa-solid fa-plus"></i> Crear aula
          </button>
        </div>
      </div>

      <!-- Grid de aulas -->
      <?php if (empty($aulas)): ?>
        <div class="empty-state">
          <i class="fa-solid fa-door-open"></i>
          <p>No hay aulas configuradas todavía. Creá la primera aula arriba.</p>
        </div>
      <?php else: ?>
      <div class="aula-grid" id="aulasGrid">
        <?php foreach ($aulas as $a): ?>
        <div class="card aula-card" id="aula-<?= $a['id'] ?>">
          <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:4px">
            <h3 style="font-size:1.05rem;font-weight:700"><?= htmlspecialchars($a['nombre']) ?></h3>
            <button class="btn btn-ghost btn-sm btn-eliminar" data-id="<?= $a['id'] ?>" data-nombre="<?= htmlspecialchars($a['nombre']) ?>"
                    title="Eliminar aula">
              <i class="fa-solid fa-trash"></i>
            </button>
          </div>
          <p style="font-size:0.78rem;color:var(--c-text-faint);margin-bottom:4px">
            Creada: <?= date('d/m/Y', strtotime($a['created_at'])) ?>
          </p>

          <div class="qr-box" id="qr-box-<?= $a['id'] ?>"></div>

          <div class="token-text"><?= htmlspecialchars($base_qr . $a['token']) ?></div>

          <button class="btn btn-ghost btn-sm btn-block mt-2 btn-download"
                  data-id="<?= $a['id'] ?>" data-nombre="<?= htmlspecialchars($a['nombre']) ?>">
            <i class="fa-solid fa-download"></i> Descargar QR
          </button>
        </div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>

    </main>
  </div>
</div>

<!-- Modal confirmación eliminar -->
<div class="modal-overlay hidden" id="modalEliminar">
  <div class="modal">
    <div class="modal-head">
      <h3>Eliminar aula</h3>
      <button class="modal-close" id="closeModalEliminar">&times;</button>
    </div>
    <div class="modal-body">
      <p>¿Seguro que querés eliminar el aula <strong id="aulaEliminarNombre"></strong>?</p>
      <p class="text-muted" style="font-size:0.88rem;margin-top:8px">
        El código QR de esta aula dejará de funcionar. Las asistencias registradas no se eliminan.
      </p>
    </div>
    <div class="modal-foot">
      <button class="btn btn-ghost" id="cancelEliminar">Cancelar</button>
      <button class="btn btn-danger" id="confirmEliminar">Eliminar</button>
    </div>
  </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script src="../assets/js/utils.js"></script>
<script>
(function() {
  const BASE_QR = <?= json_encode($base_qr) ?>;

  // Generar QR para cada aula existente
  const aulas = <?= json_encode(array_map(fn($a) => ['id'=>$a['id'],'nombre'=>$a['nombre'],'token'=>$a['token']], $aulas)) ?>;
  aulas.forEach(a => renderQR(a.id, a.token));

  function renderQR(id, token) {
    const box = document.getElementById('qr-box-' + id);
    if (!box) return;
    box.innerHTML = '';
    new QRCode(box, {
      text: BASE_QR + token,
      width: 200, height: 200,
      colorDark: '#0f172a', colorLight: '#ffffff',
      correctLevel: QRCode.CorrectLevel.M,
    });
  }

  // ── Crear aula ───────────────────────────────────────────────
  App.qs('#btnCrearAula').addEventListener('click', () => {
    const nombre = App.qs('#nombreAula').value.trim();
    if (!nombre) { App.toast('Ingresá el nombre del aula', 'error'); return; }

    App.api('../api/crear_aula.php', {
      method: 'POST', loader: true,
      body: JSON.stringify({ nombre }),
    }).then(res => {
      App.toast('Aula "' + res.nombre + '" creada correctamente', 'success');
      App.qs('#nombreAula').value = '';
      // Agregar tarjeta al grid
      const grid = App.qs('#aulasGrid') || (() => {
        const g = document.createElement('div');
        g.className = 'aula-grid';
        g.id = 'aulasGrid';
        App.qs('.app-content').appendChild(g);
        App.qs('.empty-state') && App.qs('.empty-state').remove();
        return g;
      })();

      const card = document.createElement('div');
      card.className = 'card aula-card';
      card.id = 'aula-' + res.id;
      card.innerHTML = `
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:4px">
          <h3 style="font-size:1.05rem;font-weight:700">${res.nombre}</h3>
          <button class="btn btn-ghost btn-sm btn-eliminar" data-id="${res.id}" data-nombre="${res.nombre}" title="Eliminar aula">
            <i class="fa-solid fa-trash"></i>
          </button>
        </div>
        <p style="font-size:0.78rem;color:var(--c-text-faint);margin-bottom:4px">Recién creada</p>
        <div class="qr-box" id="qr-box-${res.id}"></div>
        <div class="token-text">${BASE_QR + res.token}</div>
        <button class="btn btn-ghost btn-sm btn-block mt-2 btn-download" data-id="${res.id}" data-nombre="${res.nombre}">
          <i class="fa-solid fa-download"></i> Descargar QR
        </button>`;
      grid.appendChild(card);
      renderQR(res.id, res.token);
      bindEliminar(card.querySelector('.btn-eliminar'));
      bindDownload(card.querySelector('.btn-download'), res.token, res.nombre);
    }).catch(err => App.toast(err.message, 'error'));
  });

  // ── Eliminar aula ────────────────────────────────────────────
  let eliminarId = null;
  const modal   = App.qs('#modalEliminar');

  function bindEliminar(btn) {
    btn.addEventListener('click', () => {
      eliminarId = parseInt(btn.dataset.id);
      App.qs('#aulaEliminarNombre').textContent = btn.dataset.nombre;
      modal.classList.remove('hidden');
    });
  }
  App.qsa('.btn-eliminar').forEach(bindEliminar);
  App.qs('#closeModalEliminar').addEventListener('click', () => modal.classList.add('hidden'));
  App.qs('#cancelEliminar').addEventListener('click', () => modal.classList.add('hidden'));
  App.qs('#confirmEliminar').addEventListener('click', () => {
    if (!eliminarId) return;
    App.api('../api/eliminar_aula.php', {
      method: 'POST', loader: true,
      body: JSON.stringify({ aula_id: eliminarId }),
    }).then(() => {
      document.getElementById('aula-' + eliminarId)?.remove();
      modal.classList.add('hidden');
      App.toast('Aula eliminada', 'success');
    }).catch(err => App.toast(err.message, 'error'));
  });

  // ── Descargar QR ──────────────────────────────────────────────
  function bindDownload(btn, token, nombre) {
    btn.addEventListener('click', () => {
      // Crear QR temporal para descarga en alta resolución
      const tmp = document.createElement('div');
      tmp.style.cssText = 'position:fixed;left:-9999px;top:-9999px';
      document.body.appendChild(tmp);
      new QRCode(tmp, {
        text: BASE_QR + token,
        width: 400, height: 400,
        colorDark: '#0f172a', colorLight: '#ffffff',
        correctLevel: QRCode.CorrectLevel.M,
      });
      setTimeout(() => {
        const canvas = tmp.querySelector('canvas');
        if (!canvas) { tmp.remove(); return; }
        const link = document.createElement('a');
        link.download = 'QR-' + nombre.replace(/\s+/g,'-') + '.png';
        link.href = canvas.toDataURL('image/png');
        link.click();
        tmp.remove();
      }, 300);
    });
  }
  App.qsa('.btn-download').forEach(btn => {
    const id = parseInt(btn.dataset.id);
    const aula = aulas.find(a => a.id === id);
    if (aula) bindDownload(btn, aula.token, aula.nombre);
  });

})();
</script>
</body>
</html>
