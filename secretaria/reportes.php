<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';

require_auth(['secretaria', 'admin']);

$pdo = getPDO();

$f_materia = (int) ($_GET['materia_id'] ?? $_GET['materia'] ?? 0) ?: null;

// ── Stats globales / por materia ──────────────────────────────
if ($f_materia) {
    $prom_asist = $pdo->prepare("
        SELECT ROUND(SUM(a.estado IN ('presente','tardanza'))/NULLIF(COUNT(*),0)*100,1)
        FROM asistencias a
        JOIN clases c ON c.id = a.clase_id
        WHERE c.materia_id = ? AND c.fecha <= CURDATE()
    ");
    $prom_asist->execute([$f_materia]);
    $prom_asist = $prom_asist->fetchColumn() ?? 0;

    $stmt_pres = $pdo->prepare("
        SELECT COUNT(*)
        FROM asistencias a
        JOIN clases c ON c.id = a.clase_id
        WHERE c.materia_id = ? AND a.estado IN ('presente','tardanza')
    ");
    $stmt_pres->execute([$f_materia]);
    $total_pres = (int) $stmt_pres->fetchColumn();

    $stmt_aus = $pdo->prepare("
        SELECT COUNT(*)
        FROM asistencias a
        JOIN clases c ON c.id = a.clase_id
        WHERE c.materia_id = ? AND a.estado = 'ausente'
    ");
    $stmt_aus->execute([$f_materia]);
    $total_aus = (int) $stmt_aus->fetchColumn();

    $stmt_riesgo = $pdo->prepare("
        SELECT COUNT(*)
        FROM v_alumnos_en_riesgo
        WHERE materia_id = ?
    ");
    $stmt_riesgo->execute([$f_materia]);
    $en_riesgo = (int) $stmt_riesgo->fetchColumn();

    // ── Asistencia por materia (sólo la seleccionada) ─────────────
    $por_materia = $pdo->prepare("
        SELECT m.nombre AS materia, COALESCE(ROUND(AVG(v.porcentaje), 1), 0.0) AS pct
        FROM materias m
        LEFT JOIN v_asistencia_por_materia v ON v.materia_id = m.id
        WHERE m.id = ?
        GROUP BY m.id
    ");
    $por_materia->execute([$f_materia]);
    $por_materia = $por_materia->fetchAll(PDO::FETCH_ASSOC);

    // ── Alumnos en riesgo (filtrado) ──────────────────────────────
    $riesgo = $pdo->prepare("
        SELECT nombre, apellido, curso, porcentaje
        FROM v_alumnos_en_riesgo
        WHERE materia_id = ?
        ORDER BY porcentaje ASC
        LIMIT 10
    ");
    $riesgo->execute([$f_materia]);
    $riesgo = $riesgo->fetchAll(PDO::FETCH_ASSOC);

} else {
    $prom_asist  = $pdo->query("SELECT ROUND(SUM(estado IN ('presente','tardanza'))/NULLIF(COUNT(*),0)*100,1) FROM asistencias")->fetchColumn() ?? 0;
    $total_pres  = (int) $pdo->query("SELECT COUNT(*) FROM asistencias WHERE estado IN ('presente','tardanza')")->fetchColumn();
    $total_aus   = (int) $pdo->query("SELECT COUNT(*) FROM asistencias WHERE estado='ausente'")->fetchColumn();
    $en_riesgo   = (int) $pdo->query("SELECT COUNT(*) FROM v_alumnos_en_riesgo")->fetchColumn();

    $por_materia = $pdo->query("
        SELECT m.nombre AS materia, COALESCE(ROUND(AVG(v.porcentaje), 1), 0.0) AS pct
        FROM materias m
        LEFT JOIN v_asistencia_por_materia v ON v.materia_id = m.id
        WHERE m.activo = 1
        GROUP BY m.id
        ORDER BY pct DESC
        LIMIT 8
    ")->fetchAll(PDO::FETCH_ASSOC);

    $riesgo = $pdo->query("
        SELECT nombre, apellido, curso, porcentaje
        FROM v_alumnos_en_riesgo
        ORDER BY porcentaje ASC
        LIMIT 10
    ")->fetchAll(PDO::FETCH_ASSOC);
}

// ── Lista de materias para el select ──────────────────────────
$materias_lista = $pdo->query("
    SELECT id, nombre FROM materias WHERE activo=1 ORDER BY nombre
")->fetchAll(PDO::FETCH_ASSOC);

$partes    = explode(' ', $_SESSION['nombre']);
$iniciales = strtoupper(substr($partes[0],0,1).substr($partes[1]??'',0,1));

// Carga la Vista
require_once '../views/secretaria/reportes_view.php';



