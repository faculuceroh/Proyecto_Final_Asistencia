<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';
require_auth(['admin']);

$pdo = getPDO();

$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($_POST as $clave => $valor) {
        $valor = trim($valor);
        if ($valor === '') continue;
        $st = $pdo->prepare("UPDATE configuracion SET valor = ? WHERE clave = ?");
        $st->execute([$valor, $clave]);
        if ($st->rowCount() === 0) {
            $ins = $pdo->prepare("INSERT INTO configuracion (clave, valor) VALUES (?, ?) ON DUPLICATE KEY UPDATE valor = VALUES(valor)");
            $ins->execute([$clave, $valor]);
        }
    }
    $msg = 'ok';
}

$config = [];
$rows   = $pdo->query("SELECT clave, valor FROM configuracion")->fetchAll();
foreach ($rows as $r) {
    $config[$r['clave']] = $r['valor'];
}

$partes    = explode(' ', $_SESSION['nombre']);
$iniciales = strtoupper(substr($partes[0],0,1).substr($partes[1]??'',0,1));
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" /><meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Configuraci&oacute;n &middot; Admin</title>
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
      <div><div class="name">Asistencia QR</div><div class="sub">Administraci&oacute;n</div></div>
    </div>
    <nav class="sidebar-nav">
      <span class="nav-label">Sistema</span>
      <a href="dashboard.php"><i class="fa-solid fa-gauge-high"></i> Resumen</a>
      <a href="usuarios.php"><i class="fa-solid fa-users"></i> Usuarios</a>
      <a href="materias.php"><i class="fa-solid fa-book"></i> Materias</a>
      <a href="aulas.php"><i class="fa-solid fa-door-open"></i> Aulas</a>
      <a href="configuracion.php" class="active"><i class="fa-solid fa-gear"></i> Configuraci&oacute;n</a>
      <span class="nav-label">Cuenta</span>
      <a href="../logout.php"><i class="fa-solid fa-right-from-bracket"></i> Cerrar sesi&oacute;n</a>
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
      <button class="hamburger" data-sidebar-toggle aria-label="Menu"><i class="fa-solid fa-bars"></i></button>
      <div class="page-title">Configuraci&oacute;n <small>Par&aacute;metros del sistema</small></div>
    </header>
    <main class="app-content">

      <?php if ($msg === 'ok'): ?>
      <div class="alert alert-success" style="margin-bottom:20px">
        <i class="fa-solid fa-circle-check"></i> Configuraci&oacute;n guardada correctamente.
      </div>
      <?php endif; ?>

      <form method="POST">
        <div class="card" style="padding:28px;max-width:640px">
          <h3 style="font-size:1rem;margin-bottom:20px"><i class="fa-solid fa-sliders"></i> Par&aacute;metros generales</h3>

          <div class="field">
            <label>Tolerancia de llegada (minutos)</label>
            <p class="text-muted" style="font-size:0.83rem;margin-bottom:8px">
              Minutos de gracia despu&eacute;s del inicio de clase para registrar entrada como "presente" (en vez de "tarde").
            </p>
            <input class="input" type="number" name="tolerancia_minutos" min="0" max="60"
                   value="<?= htmlspecialchars($config['tolerancia_minutos'] ?? '15') ?>"
                   style="max-width:120px" />
          </div>

          <div class="field" style="margin-top:20px">
            <label>Tiempo de rotaci&oacute;n del QR (segundos)</label>
            <p class="text-muted" style="font-size:0.83rem;margin-bottom:8px">
              Cada cu&aacute;ntos segundos rota el token del QR din&aacute;mico (si se usa el sistema antiguo).
            </p>
            <input class="input" type="number" name="qr_rotacion_segundos" min="10" max="300"
                   value="<?= htmlspecialchars($config['qr_rotacion_segundos'] ?? '30') ?>"
                   style="max-width:120px" />
          </div>

          <div style="margin-top:28px;display:flex;gap:12px">
            <button type="submit" class="btn btn-primary">
              <i class="fa-solid fa-floppy-disk"></i> Guardar cambios
            </button>
            <a href="dashboard.php" class="btn btn-ghost">Cancelar</a>
          </div>
        </div>
      </form>

    </main>
  </div>
</div>
<script src="../assets/js/utils.js"></script>
</body>
</html>
