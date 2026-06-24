<?php
// login.php - Procesador tradicional por POST (Wrapper MVC)
require_once __DIR__ . '/../includes/db.php';

$controller = new AuthController();
$controller->login();
