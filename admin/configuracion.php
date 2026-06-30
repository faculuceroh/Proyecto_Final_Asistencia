<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';
require_auth(['admin']);

$pdo = getPDO();

$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($_POST as $clave => $valor) {
        $valor = trim($valor);
        if ($valor === '') continue;
        $st = $pdo->prepare("UPDATE configuracion SET valor = ? WHERE clave = ?");
        $st->execute([$valor, $clave]);
        if ($st->rowCount() === 0) {
            $ins = $pdo->prepare("INSERT INTO configuracion (clave, valor) VALUES (?, ?) ON DUPLICATE KEY UPDATE valor = VALUES(valor)");
            $ins->execute([$clave, $valor]);
        }
    }
    $msg = 'ok';
}

$config = [];
$rows   = $pdo->query("SELECT clave, valor FROM configuracion")->fetchAll(PDO::FETCH_ASSOC);
foreach ($rows as $r) {
    $config[$r['clave']] = $r['valor'];
}

$partes    = explode(' ', $_SESSION['nombre']);
$iniciales = strtoupper(substr($partes[0],0,1).substr($partes[1]??'',0,1));

// Carga la Vista
require_once '../views/admin/configuracion_view.php';

