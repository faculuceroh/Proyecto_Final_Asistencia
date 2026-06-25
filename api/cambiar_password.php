<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
$controller = new ApiController();
$controller->cambiarPassword();
