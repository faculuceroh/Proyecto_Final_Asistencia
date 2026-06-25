<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';
$controller = new AdminController();
$controller->usuarios();
