<?php

class Clase extends BaseModel {
    public function findById($id) {
        $stmt = $this->db->prepare('SELECT id, materia_id, fecha, hora_inicio, duracion_min, aula, modalidad, estado FROM clases WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function countToday() {
        return (int) $this->db->query("SELECT COUNT(*) FROM clases WHERE fecha = CURDATE()")->fetchColumn();
    }

    public function getTodayClassesForAlumno($alumno_id) {
        $stmt = $this->db->prepare(
            "SELECT c.id, c.hora_inicio, c.duracion_min, c.aula, c.modalidad, c.estado,
                    m.nombre AS materia, m.curso,
                    COALESCE(CONCAT(u.nombre,' ',u.apellido),'—') AS profesor,
                    a.estado   AS mi_estado,
                    TIME_FORMAT(a.hora_entrada,'%H:%i') AS entrada,
                    TIME_FORMAT(a.hora_salida, '%H:%i') AS salida
             FROM inscripciones i
             JOIN materias m ON m.id = i.materia_id
             LEFT JOIN usuarios u ON u.id = m.profesor_id
             LEFT JOIN clases c ON c.materia_id = m.id AND c.fecha = CURDATE()
             LEFT JOIN asistencias a ON a.clase_id = c.id AND a.alumno_id = i.alumno_id
             WHERE i.alumno_id = ?
             ORDER BY c.hora_inicio ASC, m.nombre ASC"
        );
        $stmt->execute([$alumno_id]);
        return $stmt->fetchAll();
    }

    public function getTodayClassesForProfesor($profesor_id) {
        $stmt = $this->db->prepare(
            'SELECT c.id, c.hora_inicio, c.duracion_min, c.aula, c.modalidad, c.estado,
                    m.nombre AS materia, m.curso,
                    (SELECT COUNT(*) FROM inscripciones WHERE materia_id = m.id) AS total_alumnos,
                    (SELECT COUNT(*) FROM asistencias WHERE clase_id = c.id AND hora_entrada IS NOT NULL) AS presentes
             FROM clases c
             JOIN materias m ON m.id = c.materia_id
             WHERE m.profesor_id = ? AND c.fecha = CURDATE()
             ORDER BY c.hora_inicio ASC'
        );
        $stmt->execute([$profesor_id]);
        return $stmt->fetchAll();
    }

    public function getClassesForProfesorLimit($profesor_id, $limit = 30) {
        $stmt = $this->db->prepare(
            "SELECT c.id, c.fecha, c.hora_inicio, c.duracion_min, c.aula, c.modalidad, c.estado,
                    m.nombre AS materia, m.curso
             FROM clases c
             JOIN materias m ON m.id = c.materia_id
             WHERE m.profesor_id = ?
             ORDER BY c.fecha DESC, c.hora_inicio DESC
             LIMIT ?"
        );
        $stmt->execute([$profesor_id, $limit]);
        return $stmt->fetchAll();
    }

    public function countAllFiltered($profesor_id = null, $materia_id = null, $fecha = '', $estado = 'finalizada') {
        $where = "WHERE c.estado = ?";
        $params = [$estado];

        if ($profesor_id) {
            $where .= ' AND m.profesor_id = ?';
            $params[] = $profesor_id;
        }
        if ($materia_id) {
            $where .= ' AND c.materia_id = ?';
            $params[] = $materia_id;
        }
        if ($fecha) {
            $where .= ' AND c.fecha = ?';
            $params[] = $fecha;
        }

        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM clases c JOIN materias m ON m.id = c.materia_id $where"
        );
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }

    public function getAllFiltered($profesor_id = null, $materia_id = null, $fecha = '', $estado = 'finalizada', $limit = null, $offset = null) {
        $where = "WHERE c.estado = ?";
        $params = [$estado];

        if ($profesor_id) {
            $where .= ' AND m.profesor_id = ?';
            $params[] = $profesor_id;
        }
        if ($materia_id) {
            $where .= ' AND c.materia_id = ?';
            $params[] = $materia_id;
        }
        if ($fecha) {
            $where .= ' AND c.fecha = ?';
            $params[] = $fecha;
        }

        $sql = "SELECT c.id,
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
                ORDER BY c.fecha DESC, m.nombre";

        if ($limit !== null && $offset !== null) {
            $sql .= " LIMIT " . (int)$limit . " OFFSET " . (int)$offset;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function checkDuplicate($materia_id, $fecha, $hora_inicio) {
        $stmt = $this->db->prepare('SELECT id FROM clases WHERE materia_id = ? AND fecha = ? AND hora_inicio = ? LIMIT 1');
        $stmt->execute([$materia_id, $fecha, $hora_inicio]);
        return $stmt->fetch();
    }

    public function create($materia_id, $fecha, $hora_inicio, $duracion_min, $aula, $modalidad) {
        $stmt = $this->db->prepare(
            'INSERT INTO clases (materia_id, fecha, hora_inicio, duracion_min, aula, modalidad)
             VALUES (?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([$materia_id, $fecha, $hora_inicio, $duracion_min, $aula ?: null, $modalidad]);
        return (int) $this->db->lastInsertId();
    }

    public function updateEstado($clase_id, $estado) {
        $stmt = $this->db->prepare('UPDATE clases SET estado = ? WHERE id = ?');
        return $stmt->execute([$estado, $clase_id]);
    }

    public function finalizar($clase_id, $materia_id) {
        // Inserta "ausente" para todos los alumnos inscriptos que no escanearon
        $this->db->prepare(
            'INSERT IGNORE INTO asistencias (alumno_id, clase_id, estado)
             SELECT i.alumno_id, ?, "ausente"
             FROM inscripciones i
             WHERE i.materia_id = ?
               AND i.alumno_id NOT IN (
                   SELECT alumno_id FROM asistencias WHERE clase_id = ?
               )'
        )->execute([$clase_id, $materia_id, $clase_id]);

        // Cuenta total ausentes
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM asistencias WHERE clase_id = ? AND estado = "ausente"');
        $stmt->execute([$clase_id]);
        $total_ausentes = (int) $stmt->fetchColumn();

        // Desactiva todos los tokens de esta clase
        $this->db->prepare('UPDATE qr_tokens SET activo = 0 WHERE clase_id = ?')
            ->execute([$clase_id]);

        // Marca la clase como finalizada
        $this->updateEstado($clase_id, 'finalizada');

        return $total_ausentes;
    }
}
