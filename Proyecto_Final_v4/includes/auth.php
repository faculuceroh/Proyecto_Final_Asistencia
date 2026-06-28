<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Verifica que haya sesión activa y que el rol sea el permitido.
 * Si no pasa la validación, redirige al login.
 *
 * Uso en cada página protegida:
 *   require_once '../includes/auth.php';
 *   require_auth(['admin']);          // solo admin
 *   require_auth(['admin','secretaria']); // varios roles
 *   require_auth();                   // cualquier usuario logueado
 *
 * @param string[] $roles  Roles permitidos. Vacío = cualquier rol autenticado.
 */
function require_auth(array $roles = []): void
{
    if (empty($_SESSION['usuario_id'])) {
        _redirigir_login();
    }
    if (!empty($roles) && !in_array($_SESSION['rol'], $roles, true)) {
        _redirigir_login();
    }
}

function _redirigir_login(): void
{
    $depth    = substr_count(trim($_SERVER['PHP_SELF'], '/'), '/');
    $base     = str_repeat('../', max(0, $depth - 1));
    $redirect = urlencode((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http')
                . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
    header('Location: ' . $base . 'index.php?redirect=' . $redirect);
    exit;
}
