<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../config/conexion.php'; // Traemos la conexión a la base de datos

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    try {
        // Pedimos los datos del usuario de manera segura (Acá actúa el rol de "Modelo")
        $sql = "SELECT id, nombre, apellido, password, rol_id FROM usuario WHERE email = :email AND activo = 1";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':email' => $email]);
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

        // Si existe y la clave matemática coincide con el hash
        if ($usuario && password_verify($password, $usuario['password'])) {
            
            if ($usuario['rol_id'] == 1) {
                // REDIRECCIÓN PROFESIONAL: Pasamos los datos del usuario por la URL para que el panel los reciba
                // (Más adelante esto lo haremos con "Sessions", pero por ahora viaja limpio por URL)
                header("Location: ../controlador/panel_controlador.php?nombre=" . urlencode($usuario['nombre']) . "&apellido=" . urlencode($usuario['apellido']));
                exit();
            } else {
                die("Acceso denegado: No tenés rol de administrador.");
            }

        } else {
            die("Credenciales incorrectas. <a href='../index.html'>Volver</a>");
        }

    } catch (\PDOException $e) {
        die("Error crítico en el controlador de login: " . $e->getMessage());
    }
} else {
    header("Location: ../index.html");
    exit();
}