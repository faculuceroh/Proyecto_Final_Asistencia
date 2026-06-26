<?php
// controller/recuperarController.php - PHPMailer + Mailtrap
session_start();

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// 🔗 CARGA FÍSICA DE LA LIBRERÍA (Acomodado a tu estructura manual)
require_once __DIR__ . '/../includes/phpmailer/Exception.php';
require_once __DIR__ . '/../includes/phpmailer/PHPMailer.php';
require_once __DIR__ . '/../includes/phpmailer/SMTP.php';
require_once __DIR__ . '/../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $legajo = isset($_POST['legajo']) ? trim($_POST['legajo']) : '';

    if ($legajo === '') {
        header("Location: ../view/password/recupero.php?status=empty");
        exit();
    }

    try {
        // 1. Buscamos el usuario por legajo en la BD
        $stmt = getPDO()->prepare('SELECT id, nombre, apellido, email FROM usuarios WHERE legajo = ? LIMIT 1');
        $stmt->execute([$legajo]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        // Seguridad: Si no existe, simulamos éxito para que no rastreen legajos válidos
        if (!$user || empty($user['email'])) {
            header("Location: ../index.php?status=enviado"); 
            exit();
        }

        // 2. Generamos el Token de 64 caracteres y su expiración de 1 hora
        $token = bin2hex(random_bytes(32)); 
        $expira = date("Y-m-d H:i:s", strtotime("+1 hour")); 

        // 3. Persistencia: Guardamos el token en tu tabla 'usuarios'
        $update = getPDO()->prepare('UPDATE usuarios SET token_recuperacion = ?, token_expira = ? WHERE id = ?');
        $update->execute([$token, $expira, $user['id']]);

        // 4. CONFIGURACIÓN E INSTANCIA DE PHPMAILER
        $mail = new PHPMailer(true);

        $mail->isSMTP();
        $mail->Host       = 'sandbox.smtp.mailtrap.io';
        $mail->SMTPAuth   = true;
        $mail->Port       = 2525;
        
        // 🔑 TUS CREDENCIALES REALES DE TU CAPTURA DE PANTALLA
        $mail->Username   = 'a19e992c158a25'; 
        $mail->Password   = '2ced0451a31367'; // Tu clave completa sin asteriscos

        // Configuración estética del envío
        $mail->setFrom('no-reply@instituto.edu.ar', 'Sistema Asistencia QR');
        $mail->addAddress($user['email'], $user['nombre'] . ' ' . $user['apellido']); 

        // 5. DISEÑO DEL CUERPO DEL CORREO EN HTML REAL
        $enlaceRecuperacion = "http://localhost/Proyecto_Final_Asistencia/view/password/restablecer_clave.php?token=" . $token;
        
        $mail->isHTML(true);
        $mail->Subject = 'Restablecer contrasenia - Asistencia QR';
        $mail->Body    = "
            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #eee; border-radius: 10px;'>
                <h2 style='color: #2b3e50; margin-bottom: 20px;'>Hola, " . htmlspecialchars($user['nombre']) . "</h2>
                <p style='color: #555; line-height: 1.6;'>Recibimos una solicitud para restablecer la contraseña de tu cuenta en el portal de <strong>Asistencia QR</strong>.</p>
                <p style='color: #555; line-height: 1.6;'>Para proceder con el cambio, hacé clic en el siguiente botón seguro:</p>
                <div style='text-align: center; margin: 30px 0;'>
                    <a href='{$enlaceRecuperacion}' style='background-color: #007bff; color: white; padding: 12px 25px; text-decoration: none; border-radius: 5px; font-weight: bold; display: inline-block; box-shadow: 0 4px 6px rgba(0,123,255,0.2);'>
                        Restablecer Contraseña
                    </a>
                </div>
                <p style='font-size: 12px; color: #777; border-top: 1px solid #eee; padding-top: 15px; margin-top: 30px;'>Este enlace tiene una validez de 1 hora por razones de seguridad. Si vos no solicitaste este cambio, podés ignorar este correo de forma segura.</p>
            </div>
        ";
        
        // Texto alternativo por si el cliente de correo no soporta HTML
        $mail->AltBody = "Hola " . $user['nombre'] . ". Ingresá a este enlace para cambiar tu clave: " . $enlaceRecuperacion;

        // ¡Fuego! Envía el mail real a la bandeja virtual de Mailtrap
        $mail->send();

        header("Location: ../index.php?status=enviado");
        exit();

    } catch (Exception $e) {
        // Log interno por si falla la conexión de PHPMailer
        error_log("Error de PHPMailer: " . $mail->ErrorInfo);
        header("Location: ../view/password/recupero.php?status=error");
        exit();
    } catch (PDOException $e) {
        header("Location: ../view/password/recupero.php?status=error_bd");
        exit();
    }
} else {
    header("Location: ../index.php");
    exit();
}
