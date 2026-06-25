<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" /><meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Mi perfil · Asistencia QR</title>
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
      <a href="historial.php"><i class="fa-solid fa-clock-rotate-left"></i> Historial</a>
      <span class="nav-label">Cuenta</span>
      <a href="perfil.php" class="active"><i class="fa-solid fa-user"></i> Mi perfil</a>
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
      <div class="page-title">Mi perfil</div>
    </header>

    <main class="app-content">
      <div class="card profile-card">
        <div class="profile-head">
          <div class="avatar-lg"><?= htmlspecialchars($iniciales) ?></div>
          <div>
            <h2><?= htmlspecialchars($user['nombre'].' '.$user['apellido']) ?></h2>
            <div class="role-line">
              <i class="fa-solid fa-chalkboard-user"></i>
              Profesor · Legajo <?= htmlspecialchars($user['legajo']) ?>
            </div>
          </div>
        </div>
        <div class="profile-body">

          <!-- Datos básicos (solo lectura) -->
          <div class="form-grid" style="margin-bottom:18px">
            <div class="field">
              <label>Nombre</label>
              <input class="input" value="<?= htmlspecialchars($user['nombre']) ?>" disabled />
            </div>
            <div class="field">
              <label>Apellido</label>
              <input class="input" value="<?= htmlspecialchars($user['apellido']) ?>" disabled />
            </div>
            <div class="field">
              <label>Legajo</label>
              <input class="input" value="<?= htmlspecialchars($user['legajo']) ?>" disabled />
            </div>
          </div>

          <div class="field" style="margin-bottom:22px">
            <label>Materias asignadas</label>
            <input class="input" value="<?= htmlspecialchars($user['materias_str'] ?? 'Sin materias asignadas') ?>" disabled />
          </div>

          <!-- Email editable -->
          <h3 style="font-size:1rem;margin-bottom:12px">Mi email</h3>
          <form id="emailForm" style="margin-bottom:26px">
            <div class="field">
              <label>Email de contacto</label>
              <input class="input" type="email" name="email"
                     value="<?= htmlspecialchars($user['email'] ?? '') ?>"
                     placeholder="tucorreo@ejemplo.com" />
            </div>
            <button type="submit" class="btn btn-primary mt-1">
              <i class="fa-solid fa-floppy-disk"></i> Guardar email
            </button>
          </form>

          <!-- Cambio de contraseña -->
          <h3 style="font-size:1rem;margin-bottom:12px">Cambiar contraseña</h3>
          <form id="passForm">
            <div class="form-grid">
              <div class="field">
                <label>Contraseña actual</label>
                <input class="input" type="password" name="password_actual" placeholder="Tu contraseña actual" required />
              </div>
              <div class="field">
                <label>Nueva contraseña</label>
                <input class="input" type="password" name="password_nueva"
                       placeholder="Mínimo 6 caracteres" minlength="6" required />
              </div>
            </div>
            <button type="submit" class="btn btn-primary mt-1">
              <i class="fa-solid fa-floppy-disk"></i> Guardar contraseña
            </button>
          </form>
        </div>
      </div>
    </main>
  </div>
</div>
<script src="../assets/js/utils.js"></script>
<script>
  App.qs('#emailForm').addEventListener('submit', function (e) {
    e.preventDefault();
    const data = Object.fromEntries(new FormData(this));
    App.api('../api/actualizar_email.php', {
      method: 'POST', loader: true,
      body: JSON.stringify(data),
    })
    .then(() => App.toast('Email guardado correctamente.', 'success'))
    .catch(err => App.toast(err.message, 'error'));
  });

  App.qs('#passForm').addEventListener('submit', function (e) {
    e.preventDefault();
    const data = Object.fromEntries(new FormData(this));
    App.api('../api/cambiar_password.php', {
      method: 'POST', loader: true,
      body: JSON.stringify(data),
    })
    .then(function () {
      App.toast('Contraseña actualizada correctamente.', 'success');
      App.qs('#passForm').reset();
    })
    .catch(err => App.toast(err.message, 'error'));
  });
</script>
</body>
</html>
