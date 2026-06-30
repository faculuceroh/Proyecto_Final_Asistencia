<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';
require_auth(['admin']);

$pdo    = getPDO();
$cursos = $pdo->query("SELECT nombre FROM cursos ORDER BY nombre")->fetchAll(PDO::FETCH_COLUMN);

$por_pagina = 15;
$pagina     = max(1, (int)($_GET['pagina'] ?? 1));
$buscar     = trim($_GET['buscar'] ?? '');
$offset     = ($pagina - 1) * $por_pagina;

if ($buscar) {
    $like = '%' . $buscar . '%';
    $cnt  = $pdo->prepare("SELECT COUNT(*) FROM usuarios WHERE (nombre LIKE ? OR apellido LIKE ? OR legajo LIKE ?) AND activo=1");
    $cnt->execute([$like, $like, $like]);
} else {
    $cnt = $pdo->query("SELECT COUNT(*) FROM usuarios WHERE activo=1");
}
$total_usuarios = (int)$cnt->fetchColumn();
$total_paginas  = max(1, (int)ceil($total_usuarios / $por_pagina));

if ($buscar) {
    $like = '%' . $buscar . '%';
    $stmt = $pdo->prepare(
        "SELECT id, legajo, nombre, apellido, rol, curso FROM usuarios
         WHERE (nombre LIKE ? OR apellido LIKE ? OR legajo LIKE ?) AND activo=1
         ORDER BY apellido, nombre LIMIT ? OFFSET ?"
    );
    $stmt->bindValue(1, $like);
    $stmt->bindValue(2, $like);
    $stmt->bindValue(3, $like);
    $stmt->bindValue(4, $por_pagina, PDO::PARAM_INT);
    $stmt->bindValue(5, $offset, PDO::PARAM_INT);
    $stmt->execute();
} else {
    $stmt = $pdo->prepare(
        "SELECT id, legajo, nombre, apellido, rol, curso FROM usuarios
         WHERE activo=1 ORDER BY created_at DESC LIMIT ? OFFSET ?"
    );
    $stmt->bindValue(1, $por_pagina, PDO::PARAM_INT);
    $stmt->bindValue(2, $offset, PDO::PARAM_INT);
    $stmt->execute();
}
$usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

$badge_rol = [
    'alumno'     => 'badge-accent',
    'profesor'   => 'badge-muted',
    'secretaria' => 'badge-warning',
    'admin'      => 'badge-danger',
];
$label_rol = [
    'alumno'     => 'Alumno',
    'profesor'   => 'Profesor',
    'secretaria' => 'Secretaria',
    'admin'      => 'Admin',
];

$partes    = explode(' ', $_SESSION['nombre']);
$iniciales = strtoupper(substr($partes[0],0,1).substr($partes[1]??'',0,1));

function url_pag_u(int $p): string {
    $q = $_GET; $q['pagina'] = $p;
    return '?' . http_build_query($q);
}

// Carga la Vista
require_once '../views/admin/usuarios_view.php';

