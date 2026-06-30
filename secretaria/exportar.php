<?php
require_once '../includes/auth.php';
require_once '../models/Clase.php';
require_once '../models/Materia.php';
require_once '../models/Usuario.php';

require_auth(['secretaria', 'admin']);

$pdo = getPDO();

// ── Filtros ───────────────────────────────────────────────────
$f_profesor = (int) ($_GET['profesor'] ?? 0) ?: null;
$f_materia  = (int) ($_GET['materia']  ?? 0) ?: null;
$f_fecha    = $_GET['fecha'] ?? '';
$f_estado   = in_array($_GET['estado'] ?? '', ['pendiente','en_curso','finalizada']) ? $_GET['estado'] : '';
$clase_id   = (int) ($_GET['clase_id'] ?? 0) ?: null;
$pagina     = max(1, (int) ($_GET['pagina'] ?? 1));
$por_pag    = 15;
$offset     = ($pagina - 1) * $por_pag;

// ── Vista detalle de una clase ────────────────────────────────
$clase_detalle = null;
$alumnos_clase = [];
$badge_asist   = ['presente'=>'badge-success','tardanza'=>'badge-warning','ausente'=>'badge-danger'];
$label_asist   = ['presente'=>'Presente','tardanza'=>'Tardanza','ausente'=>'Ausente'];

if ($clase_id) {
    $clase_detalle = Clase::getById($clase_id);

    // Obtener detalles del profesor asignado a la materia de la clase
    if ($clase_detalle) {
        $stmt = $pdo->prepare(
            "SELECT COALESCE(CONCAT(u.nombre,' ',u.apellido),'—') AS profesor
             FROM materias m
             LEFT JOIN usuarios u ON u.id = m.profesor_id
             WHERE m.id = ? LIMIT 1"
        );
        $stmt->execute([$clase_detalle['materia_id']]);
        $prof_row = $stmt->fetch();
        $clase_detalle['profesor'] = $prof_row ? $prof_row['profesor'] : '—';

        // Alumnos inscriptos con su estado de asistencia actual en esta clase
        $stmt = $pdo->prepare(
            "SELECT u.apellido, u.nombre, u.legajo,
                    COALESCE(a.estado, 'ausente') AS estado,
                    TIME_FORMAT(a.hora_entrada, '%H:%i') AS hora_entrada
             FROM inscripciones i
             JOIN usuarios u ON u.id = i.alumno_id
             LEFT JOIN asistencias a ON a.alumno_id = i.alumno_id AND a.clase_id = ?
             WHERE i.materia_id = ?
             ORDER BY u.apellido, u.nombre"
        );
        $stmt->execute([$clase_id, $clase_detalle['materia_id']]);
        $alumnos_clase = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

// ── Listas para los selects ───────────────────────────────────
$profesores = $pdo->query(
    "SELECT id, CONCAT(nombre,' ',apellido) AS nombre
     FROM usuarios WHERE rol='profesor' AND activo=1 ORDER BY apellido"
)->fetchAll(PDO::FETCH_ASSOC);

$materias_lista = $pdo->query(
    "SELECT id, nombre FROM materias WHERE activo=1 ORDER BY nombre"
)->fetchAll(PDO::FETCH_ASSOC);

// ── Stats globales ────────────────────────────────────────────
$total_clases  = (int) $pdo->query("SELECT COUNT(*) FROM clases")->fetchColumn();
$total_pres    = (int) $pdo->query("SELECT COUNT(*) FROM asistencias WHERE estado IN ('presente','tardanza')")->fetchColumn();
$total_aus     = (int) $pdo->query("SELECT COUNT(*) FROM asistencias WHERE estado='ausente'")->fetchColumn();
$prom_asist    = $pdo->query(
    "SELECT ROUND(SUM(estado IN ('presente','tardanza'))/NULLIF(COUNT(*),0)*100,1) FROM asistencias"
)->fetchColumn() ?? 0;

// ── Tabla de clases con filtros ───────────────────────────────
$where  = "WHERE c.fecha <= CURDATE()";
$params = [];

if ($f_profesor) { $where .= ' AND m.profesor_id = ?';  $params[] = $f_profesor; }
if ($f_materia)  { $where .= ' AND c.materia_id  = ?';  $params[] = $f_materia; }
if ($f_fecha)    { $where .= ' AND c.fecha        = ?';  $params[] = $f_fecha; }
if ($f_estado)   { $where .= ' AND c.estado       = ?';  $params[] = $f_estado; }

$stmt = $pdo->prepare(
    "SELECT COUNT(*) FROM clases c JOIN materias m ON m.id=c.materia_id $where"
);
$stmt->execute($params);
$total         = (int) $stmt->fetchColumn();
$total_paginas = max(1, (int) ceil($total / $por_pag));

$stmt = $pdo->prepare(
    "SELECT c.id, c.estado, c.hora_inicio,
            m.nombre AS materia, m.curso,
            CONCAT(u.nombre,' ',u.apellido) AS profesor,
            c.fecha,
            COUNT(DISTINCT i.alumno_id)                                              AS total_alum,
            SUM(a.estado IN ('presente','tardanza'))                                  AS presentes,
            COUNT(DISTINCT i.alumno_id)-COALESCE(SUM(a.estado IN ('presente','tardanza')),0) AS ausentes,
            ROUND(SUM(a.estado IN ('presente','tardanza'))/NULLIF(COUNT(DISTINCT i.alumno_id),0)*100,1) AS pct
     FROM clases c
     JOIN materias m ON m.id = c.materia_id
     LEFT JOIN usuarios u ON u.id = m.profesor_id
     LEFT JOIN inscripciones i ON i.materia_id = m.id
     LEFT JOIN asistencias a ON a.clase_id = c.id AND a.alumno_id = i.alumno_id
     $where
     GROUP BY c.id
     ORDER BY c.fecha DESC, m.nombre
     LIMIT ? OFFSET ?"
);

// Bindeamos los parámetros de limit y offset por separado o desactivamos emulación
$stmt->bindValue(count($params) + 1, $por_pag, PDO::PARAM_INT);
$stmt->bindValue(count($params) + 2, $offset, PDO::PARAM_INT);
for ($i = 0; $i < count($params); $i++) {
    $stmt->bindValue($i + 1, $params[$i]);
}
$stmt->execute();
$clases = $stmt->fetchAll(PDO::FETCH_ASSOC);

$partes    = explode(' ', $_SESSION['nombre']);
$iniciales = strtoupper(substr($partes[0],0,1) . substr($partes[1]??'',0,1));

function url_pag(int $p): string {
    $params = $_GET; $params['pagina'] = $p;
    return '?' . http_build_query($params);
}

// Carga la Vista
require_once '../views/secretaria/exportar_view.php';
