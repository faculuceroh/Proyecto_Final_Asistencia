<?php
require_once '../includes/auth.php';
require_auth(['admin']);
header('Location: ../secretaria/materias.php');
exit;
