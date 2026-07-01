<?php
// El date.timezone de XAMPP viene en Europe/Berlin por defecto; sin esto,
// date()/time() quedan desfasados respecto a la hora real de la institución
// y rompen la clasificación de tardanzas y la expiración de los QR.
date_default_timezone_set('America/Argentina/Buenos_Aires');

define('DB_HOST', 'localhost');
define('DB_NAME', 'asistencia_qr');
define('DB_USER', 'root');
define('DB_PASS', '');

/**
 * Devuelve la conexión PDO (singleton).
 * Prueba el puerto 3306 primero; si falla, reintenta con 3307.
 */
function getPDO(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        foreach ([3306, 3307] as $port) {
            try {
                $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', DB_HOST, $port, DB_NAME);
                $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
                break;
            } catch (PDOException $e) {
                if ($port === 3307) throw $e;
            }
        }
    }
    return $pdo;
}
