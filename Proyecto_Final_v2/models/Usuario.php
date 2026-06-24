<?php

class Usuario extends BaseModel {
    public function findById($id) {
        $stmt = $this->db->prepare('SELECT id, legajo, nombre, apellido, email, password, rol, curso, activo FROM usuarios WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function findByLegajo($legajo) {
        $stmt = $this->db->prepare('SELECT id, nombre, apellido, password, rol, activo FROM usuarios WHERE legajo = ? LIMIT 1');
        $stmt->execute([$legajo]);
        return $stmt->fetch();
    }

    public function countActiveByRol($rol) {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM usuarios WHERE rol = ? AND activo = 1");
        $stmt->execute([$rol]);
        return (int) $stmt->fetchColumn();
    }

    public function countAll($buscar = '', $rol = '') {
        $where = 'WHERE 1=1';
        $params = [];

        if ($buscar !== '') {
            $where .= ' AND (legajo LIKE ? OR nombre LIKE ? OR apellido LIKE ?)';
            $like = '%' . $buscar . '%';
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }
        if ($rol !== '') {
            $where .= ' AND rol = ?';
            $params[] = $rol;
        }

        $stmt = $this->db->prepare("SELECT COUNT(*) FROM usuarios $where");
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }

    public function getAll($buscar = '', $rol = '', $limit = null, $offset = null) {
        $where = 'WHERE 1=1';
        $params = [];

        if ($buscar !== '') {
            $where .= ' AND (legajo LIKE ? OR nombre LIKE ? OR apellido LIKE ?)';
            $like = '%' . $buscar . '%';
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }
        if ($rol !== '') {
            $where .= ' AND rol = ?';
            $params[] = $rol;
        }

        $sql = "SELECT id, legajo, nombre, apellido, email, rol, curso, activo FROM usuarios $where";
        
        // Sorting
        if ($buscar !== '') {
            $sql .= " ORDER BY apellido, nombre";
        } else {
            $sql .= " ORDER BY created_at DESC";
        }

        if ($limit !== null && $offset !== null) {
            $sql .= " LIMIT " . (int)$limit . " OFFSET " . (int)$offset;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function create($legajo, $nombre, $apellido, $email, $password_hash, $rol, $curso = null, $activo = 1) {
        $stmt = $this->db->prepare(
            'INSERT INTO usuarios (legajo, nombre, apellido, email, password, rol, curso, activo)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([$legajo, $nombre, $apellido, $email ?: null, $password_hash, $rol, $curso ?: null, $activo]);
        return (int) $this->db->lastInsertId();
    }

    public function toggleActivo($id, $activo) {
        $stmt = $this->db->prepare('UPDATE usuarios SET activo = ? WHERE id = ?');
        return $stmt->execute([$activo, $id]);
    }

    public function updatePassword($id, $password_hash) {
        $stmt = $this->db->prepare('UPDATE usuarios SET password = ? WHERE id = ?');
        return $stmt->execute([$password_hash, $id]);
    }
}
