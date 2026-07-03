<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';
require_auth(['admin']);

$pdo    = getPDO();
$cursos = $pdo->query("SELECT nombre FROM cursos ORDER BY nombre")->fetchAll(PDO::FETCH_COLUMN);

// El filtrado (estado/rol/búsqueda) y la paginación se hacen en el navegador
// (ver <script> en la vista), así que acá se trae la lista completa una sola vez.
$usuarios = $pdo->query(
    "SELECT id, legajo, nombre, apellido, email, rol, curso, activo FROM usuarios
     ORDER BY created_at DESC"
)->fetchAll(PDO::FETCH_ASSOC);

$partes    = explode(' ', $_SESSION['nombre']);
$iniciales = strtoupper(substr($partes[0],0,1).substr($partes[1]??'',0,1));

// Carga la Vista
require_once '../views/admin/usuarios_view.php';

