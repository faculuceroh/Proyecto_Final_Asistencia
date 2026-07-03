<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';
require_auth(['admin']);

$pdo    = getPDO();
$cursos = $pdo->query("SELECT nombre FROM cursos ORDER BY nombre")->fetchAll(PDO::FETCH_COLUMN);

$por_pagina = 15;
$pagina     = max(1, (int)($_GET['pagina'] ?? 1));
$buscar     = trim($_GET['buscar'] ?? '');
$rol_filtro = trim($_GET['rol'] ?? '');
$offset     = ($pagina - 1) * $por_pagina;

$condiciones = ["activo = 1"];
$params = [];

if ($buscar !== '') {
    $condiciones[] = "(nombre LIKE ? OR apellido LIKE ? OR legajo LIKE ?)";
    $like = '%' . $buscar . '%';
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}

if ($rol_filtro !== '' && in_array($rol_filtro, ['alumno', 'profesor', 'secretaria', 'admin'], true)) {
    $condiciones[] = "rol = ?";
    $params[] = $rol_filtro;
}

$where_clause = 'WHERE ' . implode(' AND ', $condiciones);

// Obtener total
$stmt = $pdo->prepare("SELECT COUNT(*) FROM usuarios $where_clause");
$stmt->execute($params);
$total_usuarios = (int)$stmt->fetchColumn();
$total_paginas  = max(1, (int)ceil($total_usuarios / $por_pagina));

// Obtener usuarios
$stmt = $pdo->prepare(
    "SELECT id, legajo, nombre, apellido, rol, curso FROM usuarios
     $where_clause
     ORDER BY created_at DESC LIMIT ? OFFSET ?"
);

// Enlazar parámetros dinámicos
$idx = 1;
foreach ($params as $param) {
    $stmt->bindValue($idx++, $param);
}
$stmt->bindValue($idx++, $por_pagina, PDO::PARAM_INT);
$stmt->bindValue($idx++, $offset, PDO::PARAM_INT);
$stmt->execute();
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

