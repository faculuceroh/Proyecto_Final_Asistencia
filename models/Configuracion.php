<?php

class Configuracion extends BaseModel {
    public function getAll() {
        $rows = $this->db->query('SELECT clave, valor FROM configuracion')->fetchAll();
        return array_column($rows, 'valor', 'clave');
    }

    public function getValue($clave, $default = null) {
        $stmt = $this->db->prepare('SELECT valor FROM configuracion WHERE clave = ? LIMIT 1');
        $stmt->execute([$clave]);
        $val = $stmt->fetchColumn();
        return $val !== false ? $val : $default;
    }

    public function updateConfig(array $campos) {
        $stmt = $this->db->prepare('UPDATE configuracion SET valor = ? WHERE clave = ?');
        foreach ($campos as $clave => $valor) {
            $stmt->execute([(string) $valor, $clave]);
        }
        return true;
    }
}
