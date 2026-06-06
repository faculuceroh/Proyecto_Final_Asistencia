<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Verificamos si en la URL vienen los datos del usuario. Si está vacío, lo echamos al login.
if (!isset($_GET['nombre']) || !isset($_GET['apellido'])) {
    header("Location: index.html");
    exit();
}

// Recolectamos la información limpia para que la use la vista
$admin_nombre = htmlspecialchars($_GET['nombre']);
$admin_apellido = htmlspecialchars($_GET['apellido']);

// Simulamos datos que el "Modelo" le daría a este panel (ej: comisiones para el TP)
$cantidad_comisiones = 5;
$fecha_actual = date("d/m/Y");

// LA MAGIA DEL MVC: Levantamos el archivo visual. 
// La vista va a tener acceso a las variables de arriba, pero la lógica ya terminó acá.
include '../vista/panel_vista.php';
?>