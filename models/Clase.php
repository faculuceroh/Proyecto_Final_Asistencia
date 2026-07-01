<?php
require_once __DIR__ . '/BaseModel.php';

class Clase extends BaseModel {
    /**
     * Obtiene las clases del día para un alumno.
     */
    public static function getClasesHoyAlumno($alumno_id) {
        $stmt = self::db()->prepare(
            "SELECT c.id, c.hora_inicio, c.duracion_min, c.aula, c.modalidad, c.estado,
                    m.nombre AS materia, m.curso,
                    COALESCE(CONCAT(u.nombre,' ',u.apellido),'—') AS profesor,
                    a.estado   AS mi_estado,
                    TIME_FORMAT(a.hora_entrada,'%H:%i') AS entrada,
                    TIME_FORMAT(a.hora_salida, '%H:%i') AS salida
             FROM clases c
             JOIN materias m ON m.id = c.materia_id AND m.activo = 1
             JOIN inscripciones i ON i.materia_id = c.materia_id AND i.alumno_id = ?
             LEFT JOIN usuarios u ON u.id = m.profesor_id
             LEFT JOIN asistencias a ON a.clase_id = c.id AND a.alumno_id = ?
             WHERE c.fecha = CURDATE()
             ORDER BY c.hora_inicio ASC"
        );
        $stmt->execute([$alumno_id, $alumno_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene las clases del día para un profesor.
     */
    public static function getClasesHoyProfesor($profesor_id) {
        $stmt = self::db()->prepare(
            "SELECT c.id, c.hora_inicio, c.duracion_min, c.aula, c.modalidad, c.estado,
                    m.nombre AS materia, m.curso,
                    (SELECT COUNT(*) FROM inscripciones WHERE materia_id = m.id) AS total_alumnos,
                    (SELECT COUNT(*) FROM asistencias WHERE clase_id = c.id AND estado IN ('presente','tardanza')) AS presentes
             FROM clases c
             JOIN materias m ON m.id = c.materia_id
             WHERE (m.profesor_id = ? OR m.profesor_2_id = ?) AND c.fecha = CURDATE()
             ORDER BY c.hora_inicio ASC"
        );
        $stmt->execute([$profesor_id, $profesor_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene materias asignadas al profesor con su próxima clase programada.
     */
    public static function getMateriasConProximaClaseProfesor($profesor_id) {
        $stmt = self::db()->prepare(
            'SELECT m.id, m.nombre, m.curso, m.modalidad,
                    (SELECT MIN(fecha) FROM clases
                     WHERE materia_id = m.id AND fecha >= CURDATE() AND estado = "pendiente") AS proxima
             FROM materias m
             WHERE (m.profesor_id = ? OR m.profesor_2_id = ?) AND m.activo = 1
             ORDER BY m.nombre'
        );
        $stmt->execute([$profesor_id, $profesor_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene los detalles de una clase por su ID.
     */
    public static function getById($id) {
        $stmt = self::db()->prepare(
            "SELECT c.id, c.materia_id, c.fecha, c.hora_inicio, c.duracion_min, c.aula, c.modalidad, c.estado,
                    m.nombre AS materia, m.curso
             FROM clases c
             JOIN materias m ON m.id = c.materia_id
             WHERE c.id = ? LIMIT 1"
        );
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Crea una nueva clase.
     */
    public static function create($materia_id, $fecha, $hora_inicio, $duracion_min, $aula, $modalidad, $estado = 'pendiente') {
        $stmt = self::db()->prepare(
            'INSERT INTO clases (materia_id, fecha, hora_inicio, duracion_min, aula, modalidad, estado)
             VALUES (?, ?, ?, ?, ?, ?, ?)'
        );
        return $stmt->execute([$materia_id, $fecha, $hora_inicio, $duracion_min, $aula, $modalidad, $estado]);
    }

    /**
     * Actualiza el estado de una clase (pendiente, en_curso, finalizada).
     */
    public static function updateEstado($id, $estado) {
        $stmt = self::db()->prepare('UPDATE clases SET estado = ? WHERE id = ?');
        return $stmt->execute([$estado, $id]);
    }

    /**
     * Obtiene la sesión de QR activa para un aula.
     */
    public static function getActiveQRSession($aula_id) {
        $stmt = self::db()->prepare(
            'SELECT id, clase_id, tipo, expira_en, activo
             FROM qr_sesiones
             WHERE aula_id = ? AND activo = 1
             LIMIT 1'
        );
        $stmt->execute([$aula_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Desactiva una sesión de QR específica.
     */
    public static function deactivateQRSession($session_id) {
        $stmt = self::db()->prepare('UPDATE qr_sesiones SET activo = 0 WHERE id = ?');
        return $stmt->execute([$session_id]);
    }

    /**
     * Desactiva todas las sesiones de QR activas para una clase.
     */
    public static function deactivateQRSessionsForClase($clase_id) {
        $stmt = self::db()->prepare('UPDATE qr_sesiones SET activo = 0 WHERE clase_id = ?');
        return $stmt->execute([$clase_id]);
    }

    /**
     * Crea/activa una nueva sesión de QR.
     */
    public static function createQRSession($aula_id, $clase_id, $tipo, $expira_en = null) {
        $stmt = self::db()->prepare(
            'INSERT INTO qr_sesiones (aula_id, clase_id, tipo, expira_en, activo)
             VALUES (?, ?, ?, ?, 1)'
        );
        return $stmt->execute([$aula_id, $clase_id, $tipo, $expira_en]);
    }
}
