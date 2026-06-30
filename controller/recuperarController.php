<?php
require_once __DIR__ . '/../includes/phpmailer/Exception.php';
require_once __DIR__ . '/../includes/phpmailer/PHPMailer.php';
require_once __DIR__ . '/../includes/phpmailer/SMTP.php';
require_once __DIR__ . '/../includes/db.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../index.php');
    exit;
}

$legajo = trim($_POST['legajo'] ?? '');

if ($legajo === '') {
    header('Location: ../password/recupero.php?status=empty');
    exit;
}

try {
    $stmt = getPDO()->prepare('SELECT id, nombre, apellido, email FROM usuarios WHERE legajo = ? LIMIT 1');
    $stmt->execute([$legajo]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // Simular éxito aunque no exista para no revelar legajos válidos
    if (!$user || empty($user['email'])) {
        header('Location: ../index.php?status=enviado');
        exit;
    }

    $token  = bin2hex(random_bytes(32));
    $expira = date('Y-m-d H:i:s', strtotime('+1 hour'));

    getPDO()->prepare('UPDATE usuarios SET token_recuperacion = ?, token_expira = ? WHERE id = ?')
            ->execute([$token, $expira, $user['id']]);

    // Construir enlace dinámico según el host actual (funciona con localhost y ngrok)
    $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
    $host     = $_SERVER['HTTP_HOST'];
    $dir      = dirname(dirname(str_replace('\\', '/', $_SERVER['PHP_SELF'])));
    $enlace   = "$protocol://$host$dir/password/restablecer_clave.php?token=$token";

    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Host     = 'sandbox.smtp.mailtrap.io';
    $mail->SMTPAuth = true;
    $mail->Port     = 2525;
    $mail->Username = 'a19e992c158a25';
    $mail->Password = '2ced0451a31367';

    $mail->setFrom('no-reply@instituto.edu.ar', 'Asistencia QR');
    $mail->addAddress($user['email'], $user['nombre'] . ' ' . $user['apellido']);
    $mail->isHTML(true);
    $mail->Subject = 'Restablecer contraseña · Asistencia QR';
    $mail->Body = "
        <div style='font-family:Arial,sans-serif;max-width:600px;margin:0 auto;padding:20px;border:1px solid #eee;border-radius:10px'>
            <h2 style='color:#1a2744'>Hola, {$user['nombre']}</h2>
            <p style='color:#555;line-height:1.6'>Recibimos una solicitud para restablecer la contraseña de tu cuenta en <strong>Asistencia QR</strong>.</p>
            <div style='text-align:center;margin:30px 0'>
                <a href='$enlace' style='background:#2563eb;color:white;padding:12px 25px;text-decoration:none;border-radius:6px;font-weight:bold;display:inline-block'>
                    Restablecer contraseña
                </a>
            </div>
            <p style='font-size:12px;color:#999;border-top:1px solid #eee;padding-top:15px'>Este enlace expira en 1 hora. Si no solicitaste este cambio, ignorá este correo.</p>
        </div>";
    $mail->AltBody = "Hola {$user['nombre']}. Ingresá a este enlace para cambiar tu clave: $enlace";
    $mail->send();

    header('Location: ../index.php?status=enviado');
    exit;

} catch (Exception $e) {
    error_log('PHPMailer: ' . (isset($mail) ? $mail->ErrorInfo : $e->getMessage()));
    header('Location: ../password/recupero.php?status=error');
    exit;
} catch (PDOException $e) {
    header('Location: ../password/recupero.php?status=error_bd');
    exit;
}
