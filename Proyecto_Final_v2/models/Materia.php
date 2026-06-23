<?php

class Materia extends BaseModel {
    public function findById($id) {
        $stmt = $this->db->prepare('SELECT id, nombre, codigo, curso, modalidad, profesor_id, activo FROM materias WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function findByCodigo($codigo) {
        $stmt = $this->db->prepare('SELECT id, nombre, codigo, curso, modalidad, profesor_id, activo FROM materias WHERE codigo = ? LIMIT 1');
        $stmt->execute([$codigo]);
        return $stmt->fetch();
    }

    public function countActive() {
        return (int) $this->db->query("SELECT COUNT(*) FROM materias WHERE activo = 1")->fetchColumn();
    }

    public function countDistinctProfessors() {
        return (int) $this->db->query("SELECT COUNT(DISTINCT profesor_id) FROM materias WHERE activo = 1 AND profesor_id IS NOT NULL")->fetchColumn();
    }

    public function countDistinctCursos() {
        return (int) $this->db->query("SELECT COUNT(DISTINCT curso) FROM materias WHERE activo = 1")->fetchColumn();
    }

    public function getCursos() {
        return $this->db->query("SELECT nombre FROM cursos ORDER BY nombre")->fetchAll(PDO::FETCH_COLUMN);
    }

    public function countAll($buscar = '', $curso = '') {
        $where = 'WHERE m.activo = 1';
        $params = [];

        if ($buscar !== '') {
            $where .= ' AND (m.nombre LIKE ? OR m.codigo LIKE ?)';
            $like = '%' . $buscar . '%';
            $params[] = $like;
            $params[] = $like;
        }
        if ($curso !== '') {
            $where .= ' AND m.curso = ?';
            $params[] = $curso;
        }

        $stmt = $this->db->prepare("SELECT COUNT(*) FROM materias m $where");
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }

    public function getAll($buscar = '', $curso = '', $limit = null, $offset = null) {
        $where = 'WHERE m.activo = 1';
        $params = [];

        if ($buscar !== '') {
            $where .= ' AND (m.nombre LIKE ? OR m.codigo LIKE ?)';
            $like = '%' . $buscar . '%';
            $params[] = $like;
            $params[] = $like;
        }
        if ($curso !== '') {
            $where .= ' AND m.curso = ?';
            $params[] = $curso;
        }

        $sql = "SELECT m.id, m.nombre, m.codigo, m.curso, m.modalidad,
                       COALESCE(CONCAT(u.nombre,' ',u.apellido), '—') AS profesor,
                       COUNT(i.id) AS total_alumnos
                FROM materias m
                LEFT JOIN usuarios u ON u.id = m.profesor_id
                LEFT JOIN inscripciones i ON i.materia_id = m.id
                $where
                GROUP BY m.id
                ORDER BY m.nombre ASC";

        if ($limit !== null && $offset !== null) {
            $sql .= " LIMIT " . (int)$limit . " OFFSET " . (int)$offset;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function getMateriasByProfesor($profesor_id) {
        $stmt = $this->db->prepare(
            "SELECT id, CONCAT(nombre, ' · ', curso) AS label, modalidad
             FROM materias WHERE profesor_id = ? AND activo = 1 ORDER BY nombre"
        );
        $stmt->execute([$profesor_id]);
        return $stmt->fetchAll();
    }

    public function getHorariosByMateria($materia_id) {
        $stmt = $this->db->prepare(
            'SELECT dia_semana,
                    TIME_FORMAT(hora_inicio,"%H:%i") AS hi,
                    TIME_FORMAT(hora_fin,"%H:%i")    AS hf,
                    TIMESTAMPDIFF(MINUTE, hora_inicio, hora_fin) AS dur
             FROM materia_horarios WHERE materia_id = ? ORDER BY dia_semana'
        );
        $stmt->execute([$materia_id]);
        return $stmt->fetchAll();
    }

    public function getAllActive() {
        return $this->db->query("SELECT id, nombre, curso, codigo FROM materias WHERE activo = 1 ORDER BY curso, nombre")->fetchAll();
    }

    public function create($nombre, $codigo, $curso, $modalidad, $profesor_id = null) {
        $stmt = $this->db->prepare(
            'INSERT INTO materias (nombre, codigo, curso, modalidad, profesor_id, activo)
             VALUES (?, ?, ?, ?, ?, 1)'
        );
        $stmt->execute([$nombre, $codigo, $curso, $modalidad, $profesor_id ?: null]);
        return (int) $this->db->lastInsertId();
    }
}
