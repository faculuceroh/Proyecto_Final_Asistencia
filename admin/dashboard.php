<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';
require_auth(['admin']);

$pdo = getPDO();

$cursos = $pdo->query("SELECT nombre FROM cursos ORDER BY nombre")->fetchAll(PDO::FETCH_COLUMN);

// ── Stats ────────────────────────────────────────────────────
$total_alumnos    = $pdo->query("SELECT COUNT(*) FROM usuarios WHERE rol='alumno'  AND activo=1")->fetchColumn();
$total_profesores = $pdo->query("SELECT COUNT(*) FROM usuarios WHERE rol='profesor' AND activo=1")->fetchColumn();
$clases_hoy       = $pdo->query("SELECT COUNT(*) FROM clases WHERE fecha = CURDATE()")->fetchColumn();
$asistencia_prom  = $pdo->query(
    "SELECT ROUND(SUM(estado IN ('presente','tardanza')) / NULLIF(COUNT(*),0) * 100, 1) FROM asistencias"
)->fetchColumn() ?? 0;

// ── Tabla de usuarios (paginada + búsqueda) ──────────────────
$por_pagina = 5;
$pagina     = max(1, (int) ($_GET['pagina'] ?? 1));
$buscar     = trim($_GET['buscar'] ?? '');
$rol_filtro = trim($_GET['rol'] ?? '');
$offset     = ($pagina - 1) * $por_pagina;

// Construir condiciones SQL dinámicamente
$condiciones = [];
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

$where_clause = '';
if (!empty($condiciones)) {
    $where_clause = 'WHERE ' . implode(' AND ', $condiciones);
}

// Obtener total
$sql_count = "SELECT COUNT(*) FROM usuarios $where_clause";
if (!empty($params)) {
    $stmt = $pdo->prepare($sql_count);
    $stmt->execute($params);
} else {
    $stmt = $pdo->query($sql_count);
}
$total_usuarios = (int) $stmt->fetchColumn();
$total_paginas  = (int) ceil($total_usuarios / $por_pagina);

// Obtener usuarios
$sql_users = "SELECT id, legajo, nombre, apellido, rol, activo FROM usuarios $where_clause ORDER BY created_at DESC LIMIT ? OFFSET ?";
$stmt = $pdo->prepare($sql_users);

// Enlazar parámetros dinámicos
$idx = 1;
foreach ($params as $param) {
    $stmt->bindValue($idx++, $param);
}
$stmt->bindValue($idx++, $por_pagina, PDO::PARAM_INT);
$stmt->bindValue($idx++, $offset, PDO::PARAM_INT);
$stmt->execute();
$usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ── Helpers de presentación ───────────────────────────────────
$badge_rol = [
    'alumno'     => 'badge-accent',
    'profesor'   => 'badge-muted',
    'secretaria' => 'badge-warning',
    'admin'      => 'badge-danger',
];
$label_rol = [
    'alumno'     => 'Alumno',
    'profesor'   => 'Profesor',
    'secretaria' => 'Secretaría',
    'admin'      => 'Admin',
];

// Fecha en español
$dias   = ['domingo','lunes','martes','miércoles','jueves','viernes','sábado'];
$fecha_hoy = ucfirst($dias[date('w')]) . ' ' . date('d/m/Y');

// Iniciales del usuario logueado
$partes    = explode(' ', $_SESSION['nombre']);
$iniciales = strtoupper(substr($partes[0], 0, 1) . substr($partes[1] ?? '', 0, 1));

// Carga la Vista
require_once '../views/admin/dashboard_view.php';

