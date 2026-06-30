<?php
require_once __DIR__ . '/../includes/db.php';

class BaseModel {
    /**
     * Retorna la instancia única de PDO para realizar consultas.
     */
    protected static function db() {
        return getPDO();
    }
}
