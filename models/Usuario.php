<?php
require_once __DIR__ . '/BaseModel.php';

class Usuario extends BaseModel {
    /**
     * Busca un usuario por su legajo.
     */
    public static function findByLegajo($legajo) {
        $stmt = self::db()->prepare(
            'SELECT id, nombre, apellido, password, rol, activo, email, foto
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
            'SELECT id, legajo, nombre, apellido, email, rol, curso, foto, activo, token_recuperacion, token_expira
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
     * Actualiza los datos básicos de un usuario (corrección de errores de
     * carga). No toca password, rol ni activo.
     */
    public static function update($id, $legajo, $nombre, $apellido, $email, $curso) {
        $stmt = self::db()->prepare(
            'UPDATE usuarios SET legajo = ?, nombre = ?, apellido = ?, email = ?, curso = ? WHERE id = ?'
        );
        return $stmt->execute([$legajo, $nombre, $apellido, $email, $curso, $id]);
    }

    /**
     * Actualiza el nombre de archivo de la foto de perfil (o lo limpia con null).
     */
    public static function updateFoto($id, $foto) {
        $stmt = self::db()->prepare('UPDATE usuarios SET foto = ? WHERE id = ?');
        return $stmt->execute([$foto, $id]);
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
            'SELECT nombre, apellido, legajo, email, foto,
                    (SELECT GROUP_CONCAT(nombre SEPARATOR ", ")
                     FROM materias WHERE profesor_id = u.id AND activo = 1) AS materias_str
             FROM usuarios u WHERE id = ? LIMIT 1'
        );
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
