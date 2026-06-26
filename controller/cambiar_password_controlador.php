<?php
// controller/cambiar_password_controlador.php - Guardado de clave nueva
session_start();

require_once __DIR__ . '/../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = isset($_POST['user_id']) ? trim($_POST['user_id']) : '';
    $token = isset($_POST['token']) ? trim($_POST['token']) : '';
    $password = isset($_POST['password']) ? trim($_POST['password']) : '';

    if ($user_id === '' || $token === '' || $password === '') {
        header("Location: ../index.php?status=error");
        exit();
    }

    try {
        // Encriptamos la clave con Bcrypt (Estándar nativo seguro)
        $password_encriptada = password_hash($password, PASSWORD_BCRYPT);

        // Actualizamos la password y LIMPIAMOS el token para dejarlo de un solo uso
        $stmt = getPDO()->prepare('UPDATE usuarios SET password = ?, token_recuperacion = NULL, token_expira = NULL WHERE id = ? AND token_recuperacion = ?');
        $stmt->execute([$password_encriptada, $user_id, $token]);

        // Redireccionamos al login con aviso de éxito
        header("Location: ../index.php?status=clave_cambiada");
        exit();

    } catch (PDOException $e) {
        header("Location: ../index.php?status=error");
        exit();
    }
} else {
    header("Location: ../index.php");
    exit();
}