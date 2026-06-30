<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';

require_auth(['secretaria', 'admin']);

$pdo = getPDO();

// ── Stats globales ────────────────────────────────────────────
$prom_asist  = $pdo->query("SELECT ROUND(SUM(estado IN ('presente','tardanza'))/NULLIF(COUNT(*),0)*100,1) FROM asistencias")->fetchColumn() ?? 0;
$total_pres  = (int) $pdo->query("SELECT COUNT(*) FROM asistencias WHERE estado IN ('presente','tardanza')")->fetchColumn();
$total_aus   = (int) $pdo->query("SELECT COUNT(*) FROM asistencias WHERE estado='ausente'")->fetchColumn();
$en_riesgo   = (int) $pdo->query("SELECT COUNT(*) FROM v_alumnos_en_riesgo")->fetchColumn();

// ── Asistencia por materia ────────────────────────────────────
$por_materia = $pdo->query(
    "SELECT materia, ROUND(AVG(porcentaje),1) AS pct
     FROM v_asistencia_por_materia
     GROUP BY materia_id
     ORDER BY pct DESC
     LIMIT 8"
)->fetchAll(PDO::FETCH_ASSOC);

// ── Alumnos en riesgo ─────────────────────────────────────────
$riesgo = $pdo->query(
    "SELECT nombre, apellido, curso, porcentaje
     FROM v_alumnos_en_riesgo
     ORDER BY porcentaje ASC
     LIMIT 10"
)->fetchAll(PDO::FETCH_ASSOC);

$partes    = explode(' ', $_SESSION['nombre']);
$iniciales = strtoupper(substr($partes[0],0,1).substr($partes[1]??'',0,1));

// Carga la Vista
require_once '../views/secretaria/reportes_view.php';



