<?php

class Inscripcion extends BaseModel {
    public function getInscriptosByMateria($materia_id) {
        $stmt = $this->db->prepare(
            "SELECT u.id AS alumno_id, u.legajo, u.nombre, u.apellido, u.curso AS alumno_curso
             FROM inscripciones i
             JOIN usuarios u ON u.id = i.alumno_id
             WHERE i.materia_id = ?
             ORDER BY u.apellido, u.nombre"
        );
        $stmt->execute([$materia_id]);
        return $stmt->fetchAll();
    }

    public function checkInscrito($materia_id, $alumno_id) {
        $stmt = $this->db->prepare('SELECT id FROM inscripciones WHERE materia_id = ? AND alumno_id = ? LIMIT 1');
        $stmt->execute([$materia_id, $alumno_id]);
        return $stmt->fetch();
    }

    public function inscribir($materia_id, $alumno_id) {
        if ($this->checkInscrito($materia_id, $alumno_id)) {
            return false;
        }
        $stmt = $this->db->prepare('INSERT INTO inscripciones (materia_id, alumno_id) VALUES (?, ?)');
        return $stmt->execute([$materia_id, $alumno_id]);
    }

    public function desinscribir($materia_id, $alumno_id) {
        $stmt = $this->db->prepare('DELETE FROM inscripciones WHERE materia_id = ? AND alumno_id = ?');
        return $stmt->execute([$materia_id, $alumno_id]);
    }

    public function countByAlumno($alumno_id) {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM inscripciones WHERE alumno_id = ?");
        $stmt->execute([$alumno_id]);
        return (int) $stmt->fetchColumn();
    }
}
