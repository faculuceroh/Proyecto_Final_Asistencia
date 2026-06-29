<?php
require_once __DIR__ . '/../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../index.php');
    exit;
}

$user_id  = trim($_POST['user_id']  ?? '');
$token    = trim($_POST['token']    ?? '');
$password = trim($_POST['password'] ?? '');

if ($user_id === '' || $token === '' || $password === '') {
    header('Location: ../index.php?status=error');
    exit;
}

try {
    $hash = password_hash($password, PASSWORD_BCRYPT);

    $stmt = getPDO()->prepare(
        'UPDATE usuarios SET password = ?, token_recuperacion = NULL, token_expira = NULL
         WHERE id = ? AND token_recuperacion = ?'
    );
    $stmt->execute([$hash, $user_id, $token]);

    header('Location: ../index.php?status=clave_cambiada');
    exit;

} catch (PDOException $e) {
    header('Location: ../index.php?status=error');
    exit;
}
