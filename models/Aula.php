<?php
require_once __DIR__ . '/BaseModel.php';

class Aula extends BaseModel {
    /**
     * Obtiene una lista de todas las aulas activas.
     */
    public static function getAll() {
        return self::db()->query(
            'SELECT id, nombre, token, created_at FROM aulas WHERE activo = 1 ORDER BY nombre'
        )->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Busca un aula activa por su token.
     */
    public static function findByToken($token) {
        $stmt = self::db()->prepare(
            'SELECT id, nombre FROM aulas WHERE token = ? AND activo = 1 LIMIT 1'
        );
        $stmt->execute([$token]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Busca un aula por su ID.
     */
    public static function findById($id) {
        $stmt = self::db()->prepare(
            'SELECT id, nombre, token, activo FROM aulas WHERE id = ? LIMIT 1'
        );
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Crea una nueva aula generando un token único.
     */
    public static function create($nombre) {
        $token = bin2hex(random_bytes(16));
        $stmt = self::db()->prepare(
            'INSERT INTO aulas (nombre, token, activo) VALUES (?, ?, 1)'
        );
        if ($stmt->execute([$nombre])) {
            return self::db()->lastInsertId();
        }
        return false;
    }

    /**
     * Desactiva (elimina lógicamente) un aula.
     */
    public static function toggleActive($id, $activo) {
        $stmt = self::db()->prepare('UPDATE aulas SET activo = ? WHERE id = ?');
        return $stmt->execute([(int)$activo, $id]);
    }
}
