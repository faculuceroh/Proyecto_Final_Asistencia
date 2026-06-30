<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" /><meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Importar asistencia Teams · Asistencia QR</title>
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
      <a href="historial.php"><i class="fa-solid fa-clock-rotate-left"></i> Historial</a>
      <a href="importar_teams.php" class="active"><i class="fa-brands fa-microsoft"></i> Importar Teams</a>
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
      <div class="page-title">Importar asistencia Teams</div>
    </header>

    <main class="app-content">
      <div class="card" style="max-width:640px;margin:0 auto;padding:28px 32px">

        <div style="display:flex;align-items:center;gap:14px;margin-bottom:24px">
          <div style="width:46px;height:46px;border-radius:12px;background:#e0f2fe;display:grid;place-items:center;flex-shrink:0">
            <i class="fa-brands fa-microsoft" style="color:#0ea5e9;font-size:1.3rem"></i>
          </div>
          <div>
            <h2 style="font-size:1.1rem;margin-bottom:2px">Importar asistencia virtual</h2>
            <p style="color:var(--c-text-soft);font-size:0.88rem">Subí el archivo .csv que exporta Teams al finalizar la reunión.</p>
          </div>
        </div>

        <?php if (isset($_GET['error'])): ?>
        <div style="background:var(--c-danger-soft);border:1px solid #fca5a5;border-radius:var(--r-md);padding:12px 16px;margin-bottom:20px;color:var(--c-danger);font-size:0.88rem">
          <i class="fa-solid fa-circle-exclamation"></i>
          <?= htmlspecialchars(urldecode($_GET['error'])) ?>
        </div>
        <?php endif; ?>

        <form method="post" action="procesar_importacion.php" enctype="multipart/form-data">

          <div class="field">
            <label for="clase_id">Clase virtual</label>
            <?php if (empty($clases_virtuales)): ?>
              <p style="color:var(--c-text-soft);font-size:0.9rem;padding:10px 0">
                No tenés clases con modalidad virtual asignadas.
              </p>
            <?php else: ?>
            <select class="input select" id="clase_id" name="clase_id" required>
              <option value="">— Seleccioná una clase —</option>
              <?php foreach ($clases_virtuales as $c): ?>
              <option value="<?= (int)$c['id'] ?>">
                <?= htmlspecialchars($c['materia_nombre']) ?>
                · <?= htmlspecialchars($c['curso']) ?>
                · <?= date('d/m/Y', strtotime($c['fecha'])) ?>
                <?= htmlspecialchars($c['hora_inicio']) ?>
                (<?= (int)$c['duracion_min'] ?> min)
                <?php if ($c['estado'] !== 'finalizada'): ?>
                  — <?= htmlspecialchars($c['estado']) ?>
                <?php endif; ?>
              </option>
              <?php endforeach; ?>
            </select>
            <?php endif; ?>
          </div>

          <div class="field">
            <label for="archivo_teams">Archivo de asistencia Teams (.csv)</label>
            <input class="input" type="file" id="archivo_teams" name="archivo_teams"
                   accept=".csv" required style="padding:10px 14px;cursor:pointer" />
            <small style="color:var(--c-text-faint);font-size:0.8rem;display:block;margin-top:6px">
              Exportá desde Teams → Reunión finalizada → Descargar informe de asistencia. Máx. 2 MB.
            </small>
          </div>

          <button type="submit" class="btn btn-primary btn-block" <?= empty($clases_virtuales) ? 'disabled' : '' ?>>
            <i class="fa-solid fa-file-import"></i> Procesar archivo
          </button>
        </form>

      </div>
    </main>
  </div>
</div>
<script src="../assets/js/utils.js"></script>
</body>
</html>
