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
$por_pagina = 10;
$pagina     = max(1, (int) ($_GET['pagina'] ?? 1));
$buscar     = trim($_GET['buscar'] ?? '');
$offset     = ($pagina - 1) * $por_pagina;

if ($buscar) {
    $like = '%' . $buscar . '%';
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM usuarios WHERE nombre LIKE ? OR apellido LIKE ? OR legajo LIKE ?");
    $stmt->execute([$like, $like, $like]);
} else {
    $stmt = $pdo->query("SELECT COUNT(*) FROM usuarios");
}
$total_usuarios = (int) $stmt->fetchColumn();
$total_paginas  = (int) ceil($total_usuarios / $por_pagina);

if ($buscar) {
    $like = '%' . $buscar . '%';
    $stmt = $pdo->prepare(
        "SELECT id, legajo, nombre, apellido, rol, activo
         FROM usuarios
         WHERE nombre LIKE ? OR apellido LIKE ? OR legajo LIKE ?
         ORDER BY apellido, nombre
         LIMIT ? OFFSET ?"
    );
    $stmt->bindValue(1, $like);
    $stmt->bindValue(2, $like);
    $stmt->bindValue(3, $like);
    $stmt->bindValue(4, $por_pagina, PDO::PARAM_INT);
    $stmt->bindValue(5, $offset, PDO::PARAM_INT);
    $stmt->execute();
} else {
    $stmt = $pdo->prepare(
        "SELECT id, legajo, nombre, apellido, rol, activo
         FROM usuarios ORDER BY created_at DESC
         LIMIT ? OFFSET ?"
    );
    $stmt->bindValue(1, $por_pagina, PDO::PARAM_INT);
    $stmt->bindValue(2, $offset, PDO::PARAM_INT);
    $stmt->execute();
}
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

