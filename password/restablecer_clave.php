<?php
require_once __DIR__ . '/../includes/db.php';

$token_valido = false;
$user_id      = null;
$error_msg    = '';

if (!empty(trim($_GET['token'] ?? ''))) {
    $token = trim($_GET['token']);
    try {
        $stmt = getPDO()->prepare(
            'SELECT id FROM usuarios
             WHERE token_recuperacion = ? AND token_expira >= NOW()
             LIMIT 1'
        );
        $stmt->execute([$token]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            $token_valido = true;
            $user_id      = $user['id'];
        } else {
            $error_msg = 'El enlace expiró o ya fue utilizado. Solicitá uno nuevo.';
        }
    } catch (PDOException $e) {
        $error_msg = 'Error técnico. Intentá de nuevo más tarde.';
    }
} else {
    $error_msg = 'Acceso no autorizado.';
}

// Carga la Vista
require_once '../views/public/restablecer_clave_view.php';

