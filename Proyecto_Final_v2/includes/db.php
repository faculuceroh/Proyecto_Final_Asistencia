<?php
// Credenciales de la base de datos (XAMPP por defecto: root sin contraseña)
define('DB_HOST', 'localhost');
define('DB_NAME', 'asistencia_qr');
define('DB_USER', 'root');
define('DB_PASS', '');

/**
 * Devuelve la conexión PDO (singleton).
 * Se reutiliza la misma instancia en toda la request.
 */
function getPDO(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        $dsn = sprintf('mysql:host=%s;dbname=%s;charset=utf8mb4', DB_HOST, DB_NAME);
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
    }
    return $pdo;
}

// Autoloader para MVC (carga automática de Modelos y Controladores)
spl_autoload_register(function ($class_name) {
    $base_dir = dirname(__DIR__) . '/';
    
    // Buscar en models/
    $model_file = $base_dir . 'models/' . $class_name . '.php';
    if (file_exists($model_file)) {
        require_once $model_file;
        return;
    }
    
    // Buscar en controller/
    $controller_file = $base_dir . 'controller/' . $class_name . '.php';
    if (file_exists($controller_file)) {
        require_once $controller_file;
        return;
    }
});
