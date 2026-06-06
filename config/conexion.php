<?php
// 1. Definimos las credenciales de tu XAMPP local
$host    = 'localhost';
$db      = 'asistencia-class'; // El nombre que le pusiste a tu base en phpMyAdmin
$user    = 'root';          // Por defecto en XAMPP el usuario es root
$pass    = '';              // Por defecto en XAMPP la contraseña está vacía
$charset = 'utf8mb4';       // Para que acepte eñes y acentos sin romperse

// DSN significa "Data Source Name", es la dirección de la base de datos
$dsn = "mysql:host=$host;dbname=$db;charset=$charset";

// Configuraciones de seguridad y comodidad para PDO
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Si hay un error, que frene y avise
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // Trae los datos limpios en formato de array
    PDO::ATTR_EMULATE_PREPARES   => false,                  // Desactiva la emulación para evitar inyección SQL
];

try {
    // Intentamos conectar usando la librería PDO
    $pdo = new PDO($dsn, $user, $pass, $options);
    
    // Si querés probar que funciona, descomentá la línea de abajo borrando las barras:
    // echo "¡Conexión exitosa a la base de datos de la UTN!";

} catch (\PDOException $e) {
    // Si algo falla (pusiste mal el nombre de la base, etc), frena la app y te dice qué pasó
    die("Error crítico de conexión: " . $e->getMessage());
}
?>