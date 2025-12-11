<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';
require_once 'email_config.php';

function sendPasswordResetEmail($to_email, $reset_link, $user_name) {
    // Crear una instancia; pasar true habilita excepciones
    $mail = new PHPMailer(true);

    try {
        // Configuración del servidor
        $mail->isSMTP();                                            
        $mail->Host       = SMTP_HOST;                     
        $mail->SMTPAuth   = true;                                   
        $mail->Username   = SMTP_USERNAME;                     
        $mail->Password   = SMTP_PASSWORD;                               
        $mail->SMTPSecure = (SMTP_ENCRYPTION === 'tls') ? PHPMailer::ENCRYPTION_STARTTLS : PHPMailer::ENCRYPTION_SMTPS;            
        $mail->Port       = SMTP_PORT;                                    
        $mail->CharSet    = 'UTF-8';                                      

        // Destinatario
        $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
        $mail->addAddress($to_email, $user_name);     

        // Contenido
        $mail->isHTML(true);                                  
        $mail->Subject = "Recuperación de Contraseña - Trillos Visual Records";
        
        $message = "
        <!DOCTYPE html>
        <html>
        <head>
            <style>
                body { font-family: 'Arial', sans-serif; background-color: #000000; color: #ffffff; margin: 0; padding: 0; }
                .container { max-width: 600px; margin: 20px auto; padding: 40px; background: #111111; border: 1px solid #333333; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.5); }
                .header { text-align: center; padding-bottom: 30px; border-bottom: 2px solid #FFD700; margin-bottom: 30px; }
                .logo { color: #FFD700; font-size: 28px; font-weight: bold; text-transform: uppercase; letter-spacing: 2px; }
                .content { padding: 0 10px; color: #e0e0e0; font-size: 16px; line-height: 1.6; }
                .content strong { color: #ffffff; }
                .button { box-sizing: border-box; display: block; width: 200px; margin: 30px auto; padding: 15px 20px; background: linear-gradient(45deg, #FFD700, #FDB931); color: #000000; text-decoration: none; border-radius: 6px; font-weight: bold; text-align: center; font-size: 16px; }
                .link-text { color: #FFD700; text-decoration: none; word-break: break-all; }
                .warning { background: rgba(255, 215, 0, 0.1); border-left: 4px solid #FFD700; padding: 15px; margin: 25px 0; color: #e0e0e0; border-radius: 4px; }
                .footer { text-align: center; padding-top: 30px; border-top: 1px solid #333333; margin-top: 30px; color: #666666; font-size: 12px; }
                h2 { color: #FFD700; margin-top: 0; font-size: 24px; text-align: center; margin-bottom: 25px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <div class='logo'>Trillos Visual Records</div>
                </div>
                <div class='content'>
                    <h2>Recuperación de Contraseña</h2>
                    <p>Hola <strong>" . htmlspecialchars($user_name) . "</strong>,</p>
                    <p>Hemos recibido una solicitud para restablecer la contraseña de tu cuenta.</p>
                    <p>Para crear una nueva contraseña, haz clic en el siguiente botón:</p>
                    
                    <a href='" . $reset_link . "' class='button'>Restablecer Contraseña</a>
                    
                    <p>Si el botón no funciona, copia y pega el siguiente enlace en tu navegador:</p>
                    <p style='background: #000; padding: 15px; border-radius: 6px; font-family: monospace; font-size: 13px; border: 1px solid #333;'>
                        <a href='" . $reset_link . "' class='link-text'>" . $reset_link . "</a>
                    </p>
                    
                    <div class='warning'>
                        <strong>⚠️ Importante:</strong> Por motivos de seguridad, este enlace expirará en 1 hora.
                    </div>
                    
                    <p style='color: #888; font-size: 14px; margin-top: 20px;'>Si no has solicitado este cambio, puedes ignorar este correo tranquilamente. Tu contraseña seguirá siendo la misma.</p>
                </div>
                <div class='footer'>
                    <p>&copy; " . date('Y') . " Trillos Visual Records. Todos los derechos reservados.</p>
                    <p>Este es un correo automático, por favor no respondas a este mensaje.</p>
                </div>
            </div>
        </body>
        </html>
        ";
        
        $mail->Body    = $message;
        $mail->AltBody = 'Para restablecer tu contraseña, visita: ' . $reset_link;

        $mail->send();
        return true;
    } catch (Exception $e) {
        file_put_contents('email_errors.log', date('Y-m-d H:i:s') . " - Mailer Error: {$mail->ErrorInfo}\n", FILE_APPEND);
        return false;
    }
}
?>
