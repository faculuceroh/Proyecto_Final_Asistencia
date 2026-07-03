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

// ── Tabla de usuarios ──────────────────────────────────────────
// El filtro (rol/búsqueda) y la paginación se hacen en el navegador
// (ver <script> en la vista), así que acá se trae la lista completa.
$usuarios = $pdo->query(
    "SELECT id, legajo, nombre, apellido, rol, activo FROM usuarios ORDER BY created_at DESC"
)->fetchAll(PDO::FETCH_ASSOC);

// Fecha en español
$dias   = ['domingo','lunes','martes','miércoles','jueves','viernes','sábado'];
$fecha_hoy = ucfirst($dias[date('w')]) . ' ' . date('d/m/Y');

// Iniciales del usuario logueado
$partes    = explode(' ', $_SESSION['nombre']);
$iniciales = strtoupper(substr($partes[0], 0, 1) . substr($partes[1] ?? '', 0, 1));

// Carga la Vista
require_once '../views/admin/dashboard_view.php';

