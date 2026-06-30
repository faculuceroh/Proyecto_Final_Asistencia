<?php
require_once __DIR__ . '/BaseModel.php';

class Usuario extends BaseModel {
    /**
     * Busca un usuario por su legajo.
     */
    public static function findByLegajo($legajo) {
        $stmt = self::db()->prepare(
            'SELECT id, nombre, apellido, password, rol, activo, email
             FROM usuarios WHERE legajo = ? LIMIT 1'
        );
        $stmt->execute([$legajo]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Busca un usuario por su ID.
     */
    public static function findById($id) {
        $stmt = self::db()->prepare(
            'SELECT id, legajo, nombre, apellido, email, rol, curso, activo, token_recuperacion, token_expira
             FROM usuarios WHERE id = ? LIMIT 1'
        );
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Busca un usuario por su token de recuperación.
     */
    public static function findByToken($token) {
        $stmt = self::db()->prepare(
            'SELECT id, token_expira FROM usuarios WHERE token_recuperacion = ? LIMIT 1'
        );
        $stmt->execute([$token]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Valida y autentica las credenciales de un usuario.
     */
    public static function authenticate($legajo, $password) {
        $user = self::findByLegajo($legajo);
        if ($user && password_verify($password, $user['password'])) {
            return $user;
        }
        return false;
    }

    /**
     * Obtiene el listado de usuarios paginado y con filtros opcionales.
     */
    public static function getPaginated($buscar, $por_pagina, $offset, $activo = 1) {
        $db = self::db();
        if ($buscar) {
            $like = '%' . $buscar . '%';
            $stmt = $db->prepare(
                "SELECT id, legajo, nombre, apellido, rol, curso FROM usuarios
                 WHERE (nombre LIKE ? OR apellido LIKE ? OR legajo LIKE ?) AND activo = ?
                 ORDER BY apellido, nombre LIMIT ? OFFSET ?"
            );
            $stmt->execute([$like, $like, $like, $activo, $por_pagina, $offset]);
        } else {
            $stmt = $db->prepare(
                "SELECT id, legajo, nombre, apellido, rol, curso FROM usuarios
                 WHERE activo = ? ORDER BY created_at DESC LIMIT ? OFFSET ?"
            );
            $stmt->execute([$activo, $por_pagina, $offset]);
        }
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene la cantidad total de usuarios que coinciden con la búsqueda.
     */
    public static function getCount($buscar = '', $activo = 1) {
        $db = self::db();
        if ($buscar) {
            $like = '%' . $buscar . '%';
            $stmt = $db->prepare(
                "SELECT COUNT(*) FROM usuarios WHERE (nombre LIKE ? OR apellido LIKE ? OR legajo LIKE ?) AND activo = ?"
            );
            $stmt->execute([$like, $like, $like, $activo]);
            return (int) $stmt->fetchColumn();
        } else {
            $stmt = $db->prepare("SELECT COUNT(*) FROM usuarios WHERE activo = ?");
            $stmt->execute([$activo]);
            return (int) $stmt->fetchColumn();
        }
    }

    /**
     * Crea un nuevo usuario.
     */
    public static function create($legajo, $nombre, $apellido, $email, $password, $rol, $curso = null, $activo = 1) {
        $hash = password_hash($password, PASSWORD_BCRYPT);
        $stmt = self::db()->prepare(
            'INSERT INTO usuarios (legajo, nombre, apellido, email, password, rol, curso, activo)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
        );
        return $stmt->execute([$legajo, $nombre, $apellido, $email, $hash, $rol, $curso, $activo]);
    }

    /**
     * Actualiza la contraseña de un usuario.
     */
    public static function updatePassword($id, $newPassword) {
        $hash = password_hash($newPassword, PASSWORD_BCRYPT);
        $stmt = self::db()->prepare('UPDATE usuarios SET password = ? WHERE id = ?');
        return $stmt->execute([$hash, $id]);
    }

    /**
     * Actualiza el token de recuperación de contraseña de un usuario.
     */
    public static function setRecoveryToken($id, $token, $expira) {
        $stmt = self::db()->prepare('UPDATE usuarios SET token_recuperacion = ?, token_expira = ? WHERE id = ?');
        return $stmt->execute([$token, $expira, $id]);
    }

    /**
     * Limpia el token de recuperación de contraseña de un usuario.
     */
    public static function clearRecoveryToken($id) {
        $stmt = self::db()->prepare('UPDATE usuarios SET token_recuperacion = NULL, token_expira = NULL WHERE id = ?');
        return $stmt->execute([$id]);
    }

    /**
     * Activa o desactiva un usuario.
     */
    public static function toggleActive($id, $activo) {
        $stmt = self::db()->prepare('UPDATE usuarios SET activo = ? WHERE id = ?');
        return $stmt->execute([(int)$activo, $id]);
    }

    /**
     * Obtiene el perfil del profesor con sus materias asignadas en una cadena de texto.
     */
    public static function getProfesorProfile($id) {
        $stmt = self::db()->prepare(
            'SELECT nombre, apellido, legajo, email,
                    (SELECT GROUP_CONCAT(nombre SEPARATOR ", ")
                     FROM materias WHERE profesor_id = u.id AND activo = 1) AS materias_str
             FROM usuarios u WHERE id = ? LIMIT 1'
        );
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
