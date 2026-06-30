<?php
require_once __DIR__ . '/BaseModel.php';

class Asistencia extends BaseModel {
    /**
     * Obtiene estadísticas globales de asistencia de un alumno.
     */
    public static function getStatsGlobal($alumno_id) {
        $db = self::db();
        
        $prom = $db->prepare(
            "SELECT ROUND(SUM(estado IN ('presente','tardanza'))/NULLIF(COUNT(*),0)*100,1)
             FROM asistencias WHERE alumno_id = ?"
        );
        $prom->execute([$alumno_id]);
        $pct_global = $prom->fetchColumn() ?? 0;

        $stmt = $db->prepare("SELECT COUNT(*) FROM asistencias WHERE alumno_id = ? AND estado IN ('presente','tardanza')");
        $stmt->execute([$alumno_id]);
        $presentes = (int) $stmt->fetchColumn();

        $stmt = $db->prepare("SELECT COUNT(*) FROM asistencias WHERE alumno_id = ? AND estado = 'ausente'");
        $stmt->execute([$alumno_id]);
        $ausentes = (int) $stmt->fetchColumn();

        return [
            'pct_global' => $pct_global,
            'presentes'  => $presentes,
            'ausentes'   => $ausentes
        ];
    }

    /**
     * Obtiene el historial reciente de asistencia de un alumno (límite por defecto 5).
     */
    public static function getRecientes($alumno_id, $limit = 5) {
        $stmt = self::db()->prepare(
            "SELECT m.nombre AS materia,
                    COALESCE(CONCAT(u.nombre,' ',u.apellido),'—') AS profesor,
                    c.fecha,
                    COALESCE(TIME_FORMAT(a.hora_entrada,'%H:%i'),'—') AS entrada,
                    COALESCE(TIME_FORMAT(a.hora_salida, '%H:%i'),'—') AS salida,
                    a.estado
             FROM asistencias a
             JOIN clases c ON c.id = a.clase_id
             JOIN materias m ON m.id = c.materia_id
             LEFT JOIN usuarios u ON u.id = m.profesor_id
             WHERE a.alumno_id = ?
             ORDER BY c.fecha DESC, a.updated_at DESC
             LIMIT ?"
        );
        $stmt->bindValue(1, $alumno_id, PDO::PARAM_INT);
        $stmt->bindValue(2, $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene el listado de asistencias de un alumno para una materia específica.
     */
    public static function getAsistenciasPorMateria($alumno_id, $materia_id) {
        $stmt = self::db()->prepare(
            "SELECT c.id, c.fecha, c.hora_inicio, c.duracion_min, c.estado AS estado_clase,
                    COALESCE(a.estado, 'sin_registro') AS estado_asist,
                    TIME_FORMAT(a.hora_entrada, '%H:%i') AS hora_entrada
             FROM clases c
             LEFT JOIN asistencias a ON a.clase_id = c.id AND a.alumno_id = ?
             WHERE c.materia_id = ? AND c.fecha <= CURDATE()
             ORDER BY c.fecha DESC"
        );
        $stmt->execute([$alumno_id, $materia_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene el resumen de asistencias agrupadas por materia para un alumno.
     */
    public static function getAsistenciaMateriaResumen($alumno_id) {
        $stmt = self::db()->prepare(
            "SELECT m.id, m.nombre, m.curso, m.modalidad,
                    COALESCE(CONCAT(u.nombre,' ',u.apellido),'—') AS profesor,
                    COUNT(DISTINCT c.id) AS total_clases,
                    COALESCE(SUM(a.estado IN ('presente','tardanza')), 0) AS presentes,
                    COALESCE(SUM(a.estado = 'ausente'), 0) AS ausentes,
                    ROUND(SUM(a.estado IN ('presente','tardanza'))/NULLIF(COUNT(DISTINCT c.id),0)*100,1) AS pct
             FROM inscripciones i
             JOIN materias m ON m.id = i.materia_id AND m.activo = 1
             LEFT JOIN usuarios u ON u.id = m.profesor_id
             LEFT JOIN clases c ON c.materia_id = m.id AND c.estado = 'finalizada'
             LEFT JOIN asistencias a ON a.clase_id = c.id AND a.alumno_id = i.alumno_id
             WHERE i.alumno_id = ?
             GROUP BY m.id
             ORDER BY m.nombre"
        );
        $stmt->execute([$alumno_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Busca un registro de asistencia por alumno y clase.
     */
    public static function findRegistro($alumno_id, $clase_id) {
        $stmt = self::db()->prepare(
            'SELECT id, hora_entrada, hora_salida, estado FROM asistencias WHERE alumno_id = ? AND clase_id = ? LIMIT 1'
        );
        $stmt->execute([$alumno_id, $clase_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Registra o actualiza la asistencia (entrada/salida).
     */
    public static function registrarEntrada($alumno_id, $clase_id, $estado, $hora_entrada) {
        $stmt = self::db()->prepare(
            'INSERT INTO asistencias (alumno_id, clase_id, estado, hora_entrada, updated_at)
             VALUES (?, ?, ?, ?, NOW())'
        );
        return $stmt->execute([$alumno_id, $clase_id, $estado, $hora_entrada]);
    }

    /**
     * Registra la salida en una asistencia existente.
     */
    public static function registrarSalida($alumno_id, $clase_id, $hora_salida) {
        $stmt = self::db()->prepare(
            'UPDATE asistencias SET hora_salida = ?, updated_at = NOW() WHERE alumno_id = ? AND clase_id = ?'
        );
        return $stmt->execute([$hora_salida, $alumno_id, $clase_id]);
    }
}
