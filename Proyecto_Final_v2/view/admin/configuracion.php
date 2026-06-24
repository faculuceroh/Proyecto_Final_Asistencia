<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Configuración · Administración</title>
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
      <a href="materias.php"><i class="fa-solid fa-book"></i> Materias</a>
      <a href="configuracion.php" class="active"><i class="fa-solid fa-gear"></i> Configuración</a>
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
      <div class="page-title">Configuración <small>Parámetros generales del sistema</small></div>
    </header>

    <main class="app-content">
      <form method="POST" action="configuracion.php" style="display:grid;gap:18px;max-width:720px">

        <!-- Institución -->
        <div class="card" style="padding:22px">
          <h3 style="font-size:1.05rem;margin-bottom:16px">
            <i class="fa-solid fa-building-columns" style="color:var(--c-text-faint)"></i> Institución
          </h3>
          <div class="form-grid">
            <div class="field">
              <label>Nombre del instituto</label>
              <input class="input" name="nombre_institucion"
                     value="<?= htmlspecialchars($config['nombre_institucion'] ?? '') ?>" required />
            </div>
            <div class="field">
              <label>Período activo</label>
              <input class="input" name="periodo_activo"
                     value="<?= htmlspecialchars($config['periodo_activo'] ?? '') ?>" required />
            </div>
          </div>
        </div>

        <!-- QR -->
        <div class="card" style="padding:22px">
          <h3 style="font-size:1.05rem;margin-bottom:16px">
            <i class="fa-solid fa-qrcode" style="color:var(--c-text-faint)"></i> Código QR
          </h3>
          <div class="form-grid">
            <div class="field">
              <label>Rotación del QR (segundos)</label>
              <input class="input" type="number" name="qr_rotacion_segundos"
                     min="10" max="120"
                     value="<?= (int) ($config['qr_rotacion_segundos'] ?? 30) ?>" />
            </div>
            <div class="field">
              <label>Tolerancia de llegada (minutos)</label>
              <input class="input" type="number" name="tolerancia_minutos"
                     min="0" max="60"
                     value="<?= (int) ($config['tolerancia_minutos'] ?? 10) ?>" />
            </div>
          </div>
        </div>

        <!-- Notificaciones -->
        <div class="card" style="padding:22px">
          <h3 style="font-size:1.05rem;margin-bottom:16px">
            <i class="fa-solid fa-bell" style="color:var(--c-text-faint)"></i> Notificaciones
          </h3>
          <label style="display:flex;align-items:center;gap:12px;font-size:0.9rem">
            <span class="switch">
              <input type="checkbox" name="notificaciones_activas"
                     <?= ($config['notificaciones_activas'] ?? '0') === '1' ? 'checked' : '' ?>>
              <span class="slider"></span>
            </span>
            Enviar reportes a secretaría al finalizar la clase
          </label>
        </div>

        <div>
          <button type="submit" class="btn btn-primary btn-lg">
            <i class="fa-solid fa-floppy-disk"></i> Guardar configuración
          </button>
        </div>
      </form>
    </main>
  </div>
</div>

<?php if (isset($_GET['guardado'])): ?>
<script>
  document.addEventListener('DOMContentLoaded', function () {
    App.toast('Configuración guardada correctamente.', 'success');
  });
</script>
<?php endif; ?>

<script src="../assets/js/utils.js"></script>
</body>
</html>
