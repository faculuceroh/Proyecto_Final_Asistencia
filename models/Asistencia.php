<?php

class Asistencia extends BaseModel {
    public function getPromedioGlobal() {
        return $this->db->query(
            "SELECT ROUND(SUM(estado IN ('presente','tardanza')) / NULLIF(COUNT(*),0) * 100, 1) FROM asistencias"
        )->fetchColumn() ?? 0;
    }

    public function getTotalPresentes() {
        return (int) $this->db->query("SELECT COUNT(*) FROM asistencias WHERE estado IN ('presente','tardanza')")->fetchColumn();
    }

    public function getTotalAusentes() {
        return (int) $this->db->query("SELECT COUNT(*) FROM asistencias WHERE estado = 'ausente'")->fetchColumn();
    }

    public function getPromedioAlumno($alumno_id) {
        $stmt = $this->db->prepare(
            "SELECT ROUND(SUM(estado IN ('presente','tardanza'))/NULLIF(COUNT(*),0)*100,1)
             FROM asistencias WHERE alumno_id = ?"
        );
        $stmt->execute([$alumno_id]);
        return $stmt->fetchColumn() ?? 0;
    }

    public function getPresentesAlumno($alumno_id) {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM asistencias WHERE alumno_id = ? AND estado IN ('presente','tardanza')");
        $stmt->execute([$alumno_id]);
        return (int) $stmt->fetchColumn();
    }

    public function getAusentesAlumno($alumno_id) {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM asistencias WHERE alumno_id = ? AND estado = 'ausente'");
        $stmt->execute([$alumno_id]);
        return (int) $stmt->fetchColumn();
    }

    public function getRecientesAlumno($alumno_id, $limit = 5) {
        $stmt = $this->db->prepare(
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
        $stmt->execute([$alumno_id, $limit]);
        return $stmt->fetchAll();
    }

    public function countAllFilteredForAlumno($alumno_id, $materia_id = null, $estado = '') {
        $where = "WHERE a.alumno_id = ?";
        $params = [$alumno_id];

        if ($materia_id) {
            $where .= ' AND m.id = ?';
            $params[] = $materia_id;
        }
        if ($estado) {
            $where .= ' AND a.estado = ?';
            $params[] = $estado;
        }

        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM asistencias a
             JOIN clases c ON c.id = a.clase_id
             JOIN materias m ON m.id = c.materia_id
             $where"
        );
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }

    public function getAllFilteredForAlumno($alumno_id, $materia_id = null, $estado = '', $limit = null, $offset = null) {
        $where = "WHERE a.alumno_id = ?";
        $params = [$alumno_id];

        if ($materia_id) {
            $where .= ' AND m.id = ?';
            $params[] = $materia_id;
        }
        if ($estado) {
            $where .= ' AND a.estado = ?';
            $params[] = $estado;
        }

        $sql = "SELECT m.nombre AS materia, m.curso,
                       c.fecha,
                       TIME_FORMAT(c.hora_inicio,'%H:%i') AS hora,
                       a.estado,
                       COALESCE(TIME_FORMAT(a.hora_entrada,'%H:%i'),'—') AS hora_entrada
                FROM asistencias a
                JOIN clases c ON c.id = a.clase_id
                JOIN materias m ON m.id = c.materia_id
                $where
                ORDER BY c.fecha DESC, m.nombre";

        if ($limit !== null && $offset !== null) {
            $sql .= " LIMIT " . (int)$limit . " OFFSET " . (int)$offset;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function getStatsForAlumno($alumno_id, $materia_id = null) {
        $sql = "SELECT
                   COUNT(*) AS total,
                   SUM(a.estado IN ('presente','tardanza')) AS presentes,
                   SUM(a.estado = 'ausente') AS ausentes,
                   ROUND(SUM(a.estado IN ('presente','tardanza'))/NULLIF(COUNT(*),0)*100,1) AS pct
                 FROM asistencias a
                 JOIN clases c ON c.id = a.clase_id
                 JOIN materias m ON m.id = c.materia_id
                 WHERE a.alumno_id = ?";
        $params = [$alumno_id];
        
        if ($materia_id) {
            $sql .= ' AND m.id = ?';
            $params[] = $materia_id;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch();
    }

    public function countAllFiltered($profesor_id, $materia_id = null, $fecha = '', $estado = '') {
        $where = 'WHERE m.profesor_id = ?';
        $params = [$profesor_id];
        if ($materia_id) { $where .= ' AND c.materia_id = ?'; $params[] = $materia_id; }
        if ($fecha)   { $where .= ' AND c.fecha = ?';      $params[] = $fecha; }
        if ($estado)  { $where .= ' AND a.estado = ?';     $params[] = $estado; }

        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM asistencias a
             JOIN clases c ON c.id = a.clase_id
             JOIN materias m ON m.id = c.materia_id
             $where"
        );
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }

    public function getAllFiltered($profesor_id, $materia_id = null, $fecha = '', $estado = '', $limit = null, $offset = null) {
        $where = 'WHERE m.profesor_id = ?';
        $params = [$profesor_id];
        if ($materia_id) { $where .= ' AND c.materia_id = ?'; $params[] = $materia_id; }
        if ($fecha)   { $where .= ' AND c.fecha = ?';      $params[] = $fecha; }
        if ($estado)  { $where .= ' AND a.estado = ?';     $params[] = $estado; }

        $sql = "SELECT u.apellido, u.nombre, u.legajo,
                       m.nombre AS materia,
                       c.fecha,
                       COALESCE(TIME_FORMAT(a.hora_entrada,'%H:%i'),'—') AS hora,
                       a.estado
                FROM asistencias a
                JOIN usuarios u ON u.id = a.alumno_id
                JOIN clases c ON c.id = a.clase_id
                JOIN materias m ON m.id = c.materia_id
                $where
                ORDER BY c.fecha DESC, u.apellido";

        if ($limit !== null && $offset !== null) {
            $sql .= " LIMIT " . (int)$limit . " OFFSET " . (int)$offset;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function getStatsFiltered($profesor_id, $materia_id = null, $fecha = '') {
        $where = 'WHERE m.profesor_id = ?';
        $params = [$profesor_id];
        if ($materia_id) { $where .= ' AND c.materia_id = ?'; $params[] = $materia_id; }
        if ($fecha)   { $where .= ' AND c.fecha = ?';      $params[] = $fecha; }

        $stmt = $this->db->prepare(
            "SELECT
                SUM(a.estado IN ('presente','tardanza')) AS presentes,
                SUM(a.estado = 'ausente')                AS ausentes
             FROM asistencias a
             JOIN clases c ON c.id = a.clase_id
             JOIN materias m ON m.id = c.materia_id
             $where"
        );
        $stmt->execute($params);
        return $stmt->fetch();
    }

    public function getAlumnosEnRiesgoCount() {
        return (int) $this->db->query("SELECT COUNT(*) FROM v_alumnos_en_riesgo")->fetchColumn();
    }

    public function getAlumnosEnRiesgoLimit($limit = 10) {
        $stmt = $this->db->prepare(
            "SELECT nombre, apellido, curso, porcentaje, legajo
             FROM v_alumnos_en_riesgo
             ORDER BY porcentaje ASC
             LIMIT ?"
        );
        $stmt->execute([$limit]);
        return $stmt->fetchAll();
    }

    public function getAsistenciaPorMateriaLimit($limit = 8) {
        $stmt = $this->db->prepare(
            "SELECT materia, ROUND(AVG(porcentaje),1) AS pct
             FROM v_asistencia_por_materia
             GROUP BY materia_id
             ORDER BY pct DESC
             LIMIT ?"
        );
        $stmt->execute([$limit]);
        return $stmt->fetchAll();
    }

    public function checkAsistencia($alumno_id, $clase_id) {
        $stmt = $this->db->prepare('SELECT id, estado, hora_entrada, hora_salida FROM asistencias WHERE alumno_id = ? AND clase_id = ? LIMIT 1');
        $stmt->execute([$alumno_id, $clase_id]);
        return $stmt->fetch();
    }

    public function registrarEntrada($alumno_id, $clase_id, $estado) {
        $stmt = $this->db->prepare(
            'INSERT INTO asistencias (alumno_id, clase_id, hora_entrada, estado)
             VALUES (?, ?, NOW(), ?)
             ON DUPLICATE KEY UPDATE
               hora_entrada = VALUES(hora_entrada),
               estado = VALUES(estado)'
        );
        return $stmt->execute([$alumno_id, $clase_id, $estado]);
    }

    public function registrarSalida($alumno_id, $clase_id) {
        $stmt = $this->db->prepare(
            'UPDATE asistencias
             SET hora_salida = NOW()
             WHERE alumno_id = ? AND clase_id = ?'
        );
        return $stmt->execute([$alumno_id, $clase_id]);
    }
}
