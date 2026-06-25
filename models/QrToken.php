<?php

class QrToken extends BaseModel {
    public function verifyToken($token, $clase_id) {
        $stmt = $this->db->prepare(
            'SELECT id FROM qr_tokens
             WHERE token = ? AND clase_id = ? AND activo = 1 AND expira_en > NOW()
             LIMIT 1'
        );
        $stmt->execute([$token, $clase_id]);
        return (bool) $stmt->fetch();
    }

    public function deactivateAllForClass($clase_id) {
        $stmt = $this->db->prepare('UPDATE qr_tokens SET activo = 0 WHERE clase_id = ?');
        return $stmt->execute([$clase_id]);
    }

    public function createToken($clase_id, $token, $rotacion, $tipo = 'entrada') {
        $stmt = $this->db->prepare(
            'INSERT INTO qr_tokens (clase_id, token, tipo, expira_en)
             VALUES (?, ?, ?, DATE_ADD(NOW(), INTERVAL ? SECOND))'
        );
        return $stmt->execute([$clase_id, $token, $tipo, $rotacion + 5]); // +5s margin
    }
}
