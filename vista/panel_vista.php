<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Panel de Administración - UTN Haedo</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; background: #f4f6f9; }
        .header { background: #1a73e8; color: white; padding: 20px; display: flex; justify-content: space-between; align-items: center; }
        .header h1 { margin: 0; font-size: 22px; }
        .main-content { max-width: 900px; margin: 40px auto; padding: 25px; background: white; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); }
        .info-card { background: #e8f0fe; border-left: 5px solid #1a73e8; padding: 15px; border-radius: 4px; margin-bottom: 20px; }
        .logout-btn { background: #dc3545; color: white; padding: 8px 12px; text-decoration: none; border-radius: 4px; }
    </style>
</head>
<body>

<header class="header">
    <h1>🛡️ Panel de Control - Administrador</h1>
    <span>Operador: <?php echo $admin_nombre . " " . $admin_apellido; ?></span>
</header>

<div class="main-content">
    <div class="info-card">
        <h3>¡Bienvenido de vuelta, <?php echo $admin_nombre; ?>!</h3>
        <p>Fecha de ingreso al sistema: <strong><?php echo $fecha_actual; ?></strong></p>
    </div>

    <h3>Módulos del Sistema de Asistencia:</h3>
    <p>Actualmente tenés <strong><?php echo $cantidad_comisiones; ?></strong> comisiones listas para auditar.</p>
    
    <ul>
        <li>Subir archivo masivo de alumnos (.csv)</li>
        <li>Generar reportes de presentismo mensual</li>
    </ul>

    <br>
    <a href="../index.html" class="logout-btn">Cerrar Sesión Segura</a>
</div>

</body>
</html>