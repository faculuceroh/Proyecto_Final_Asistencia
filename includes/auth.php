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

/**
 * Calcula un rango inteligente de páginas a mostrar en los controles de paginación
 * para evitar mostrar excesivas páginas (ej. 1 ... 4 5 6 ... 50).
 *
 * @param int $current Página actual.
 * @param int $total Total de páginas.
 * @return array Lista de números de página y el separador '…'.
 */
function get_page_range(int $current, int $total): array
{
    if ($total <= 7) {
        return range(1, $total);
    }
    if ($current <= 4) {
        return [1, 2, 3, 4, 5, '…', $total];
    }
    if ($current >= $total - 3) {
        return [1, '…', $total - 4, $total - 3, $total - 2, $total - 1, $total];
    }
    return [1, '…', $current - 1, $current, $current + 1, '…', $total];
}

