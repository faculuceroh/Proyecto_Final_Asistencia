<?php
require_once '../includes/auth.php';
require_auth(['profesor', 'admin']);
header('Location: dashboard.php');
exit;
