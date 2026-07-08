<?php
require_once __DIR__ . '/BaseModel.php';

class Materia extends BaseModel {
    /**
     * Obtiene las materias inscriptas de un alumno, incluyendo su porcentaje de asistencia.
     */
    public static function getInscriptas($alumno_id) {
        $stmt = self::db()->prepare(
            "SELECT m.id, m.nombre, m.curso, m.modalidad,
                    COALESCE(CONCAT(u.nombre,' ',u.apellido),'—') AS profesor,
                    ROUND(
                      SUM(a.estado IN ('presente','tardanza'))
                      / NULLIF(COUNT(DISTINCT c.id), 0) * 100, 1
                    ) AS pct,
                    COUNT(DISTINCT c.id) AS total_clases
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
     * Obtiene el porcentaje global de asistencia del alumno sobre las clases de sus materias inscriptas.
     */
    public static function getInscriptasPctGlobal($alumno_id) {
        $stmt = self::db()->prepare(
            "SELECT ROUND(SUM(a.estado IN ('presente','tardanza'))/NULLIF(COUNT(*),0)*100,1)
             FROM asistencias a
             JOIN clases c ON c.id = a.clase_id AND c.estado = 'finalizada'
             JOIN inscripciones i ON i.materia_id = c.materia_id AND i.alumno_id = a.alumno_id
             WHERE a.alumno_id = ?"
        );
        $stmt->execute([$alumno_id]);
        return $stmt->fetchColumn() ?? 0;
    }

    /**
     * Obtiene los horarios de una materia.
     */
    public static function getHorarios($materia_id) {
        $stmt = self::db()->prepare(
            'SELECT dia_semana, hora_inicio, hora_fin
             FROM materia_horarios WHERE materia_id = ? ORDER BY dia_semana'
        );
        $stmt->execute([$materia_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene los detalles de una materia específica por su ID.
     */
    public static function getById($id) {
        $stmt = self::db()->prepare(
            "SELECT m.id, m.nombre, m.curso, m.modalidad, m.profesor_id, m.activo,
                    COALESCE(CONCAT(u.nombre,' ',u.apellido),'—') AS profesor
             FROM materias m
             LEFT JOIN usuarios u ON u.id = m.profesor_id
             WHERE m.id = ? LIMIT 1"
        );
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Verifica si un alumno está inscripto en una materia.
     */
    public static function isInscripto($alumno_id, $materia_id) {
        $stmt = self::db()->prepare(
            "SELECT COUNT(*) FROM inscripciones WHERE alumno_id = ? AND materia_id = ?"
        );
        $stmt->execute([$alumno_id, $materia_id]);
        return (int) $stmt->fetchColumn() > 0;
    }

    /**
     * Obtiene todas las materias.
     */
    public static function getAll() {
        return self::db()->query(
            "SELECT m.id, m.nombre, m.curso, m.modalidad, m.activo, m.profesor_id,
                    COALESCE(CONCAT(u.nombre,' ',u.apellido),'—') AS profesor
             FROM materias m
             LEFT JOIN usuarios u ON u.id = m.profesor_id
             ORDER BY m.nombre"
        )->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Crea una nueva materia.
     */
    public static function create($nombre, $curso, $modalidad, $profesor_id = null, $activo = 1) {
        $stmt = self::db()->prepare(
            'INSERT INTO materias (nombre, curso, modalidad, profesor_id, activo)
             VALUES (?, ?, ?, ?, ?)'
        );
        return $stmt->execute([$nombre, $curso, $modalidad, $profesor_id, $activo]);
    }

    /**
     * Modifica una materia.
     */
    public static function update($id, $nombre, $curso, $modalidad, $profesor_id = null) {
        $stmt = self::db()->prepare(
            'UPDATE materias SET nombre = ?, curso = ?, modalidad = ?, profesor_id = ? WHERE id = ?'
        );
        return $stmt->execute([$nombre, $curso, $modalidad, $profesor_id, $id]);
    }

    /**
     * Habilita/Deshabilita una materia.
     */
    public static function toggleActive($id, $activo) {
        $stmt = self::db()->prepare('UPDATE materias SET activo = ? WHERE id = ?');
        return $stmt->execute([(int)$activo, $id]);
    }
}
