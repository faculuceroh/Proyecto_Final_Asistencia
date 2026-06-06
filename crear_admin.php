<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../config/conexion.php'; //conexion a la base de datos

try{
    $admin_email = "admin_principal@admin.frh.utn.edu.ar";// Email del admin principal
    $admin_password = "admin123"; // Contraseña sin hash para el admin principal

    $hash_seguro= password_hash($admin_password, PASSWORD_BCRYPT); // Hash seguro con BCRYPT

    // Verificar si el admin ya existe para evitar duplicados
    $sql_verificar = "DELETE  FROM usuario WHERE email = :email";// Consulta para verificar si el 
    // email ya existe, usando marcador de posición para seguridad.
    $stmt = $pdo->prepare($sql_verificar);// Preparar la consulta para evitar inyecciones SQL.
    $stmt->execute([':email' => $admin_email]);// Ejecutar la consulta con el email del admin.
    //borramos el admin si existe para evitar duplicados-

    $sql_insertar = "INSERT INTO usuario(nombre,apellido,dni,email,password,rol_id,activo) 
    VALUES('admin','principal',:dni,:email,:password,1,1)";

    $stmt_insertar=$pdo->prepare($sql_insertar);
    $stmt_insertar->execute([
        ':dni'=>'12345678',
        ':email'=>$admin_email,
        ':password'=>$hash_seguro
    ]);// Ejecutar la consulta con los datos del admin.de esta forma se asegura que el email y 
    // la contraseña se manejen de forma segura, evitando inyecciones SQL y almacenando la contraseña de manera segura con hashing.

// 8. El "echo" híbrido para ver el cartel gráfico en Chrome
    echo "<div style='font-family: Arial, sans-serif; max-width: 550px; margin: 50px auto; padding: 25px; background: #e8f0fe; border: 1px solid #1a73e8; border-radius: 8px;'>";
    echo "<h2 style='color: #1a73e8; margin-top: 0;'>🛡️ ¡Sembrador de Base de Datos Exitoso!</h2>";
    echo "<hr style='border:0; border-top: 1px solid #1a73e8;'>";
    echo "<p>El usuario Administrador se inyectó directamente desde PHP a MySQL.</p>";
    echo "<p><strong>Usuario (Email):</strong> <code>" . htmlspecialchars($admin_email) . "</code></p>";
    echo "<p><strong>Contraseña:</strong> <code>" . htmlspecialchars($admin_password ) . "</code></p>";
    echo "<p style='font-size: 12px; color: #666;'><em>El hash guardado mide " . strlen($hash_seguro) . " caracteres y empieza con " . substr($hash_seguro, 0, 7) . "...</em></p>";
    echo "</div>";

} catch (\PDOException $e) {
    // Si la base de datos falla (ej: tabla no existe), frena todo y muestra el por qué
    die("Error crítico al sembrar el Administrador: " . $e->getMessage());
}
?>
