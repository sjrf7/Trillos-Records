<?php
// Simple Email Sender using PHP mail() function
// This is a fallback solution that doesn't require PHPMailer/Composer

function sendPasswordResetEmail($to_email, $reset_link, $user_name) {
    $subject = "Recuperación de Contraseña - Trillos Visual Records";
    
    // HTML Email Template
    $message = "
    <!DOCTYPE html>
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; background-color: #000; color: #fff; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; background: linear-gradient(145deg, rgba(20, 20, 20, 0.95), rgba(10, 10, 10, 0.98)); border: 1px solid rgba(255, 215, 0, 0.3); border-radius: 10px; }
            .header { text-align: center; padding: 20px 0; border-bottom: 2px solid #FFD700; }
            .logo { color: #FFD700; font-size: 24px; font-weight: bold; }
            .content { padding: 30px 20px; }
            .button { display: inline-block; padding: 15px 30px; background: linear-gradient(45deg, #FFD700, #FDB931); color: #000; text-decoration: none; border-radius: 8px; font-weight: bold; margin: 20px 0; }
            .footer { text-align: center; padding: 20px; color: #888; font-size: 12px; border-top: 1px solid #333; }
            .warning { background: rgba(255, 215, 0, 0.1); border-left: 3px solid #FFD700; padding: 10px; margin: 15px 0; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <div class='logo'>Trillos Visual Records</div>
            </div>
            <div class='content'>
                <h2 style='color: #FFD700;'>Recuperación de Contraseña</h2>
                <p>Hola <strong>" . htmlspecialchars($user_name) . "</strong>,</p>
                <p>Recibimos una solicitud para restablecer la contraseña de tu cuenta.</p>
                <p>Haz clic en el siguiente botón para crear una nueva contraseña:</p>
                <p style='text-align: center;'>
                    <a href='" . $reset_link . "' class='button'>Restablecer Contraseña</a>
                </p>
                <p>O copia y pega este enlace en tu navegador:</p>
                <p style='word-break: break-all; background: #111; padding: 10px; border-radius: 5px; font-size: 12px;'>" . $reset_link . "</p>
                <div class='warning'>
                    <strong>⚠️ Importante:</strong> Este enlace expirará en 1 hora por seguridad.
                </div>
                <p style='color: #888; font-size: 14px;'>Si no solicitaste este cambio, puedes ignorar este correo. Tu contraseña permanecerá sin cambios.</p>
            </div>
            <div class='footer'>
                <p>&copy; " . date('Y') . " Trillos Visual Records. Todos los derechos reservados.</p>
                <p>Este es un correo automático, por favor no respondas a este mensaje.</p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    // Headers for HTML email
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= "From: Trillos Visual Records <noreply@trillosrecords.com>" . "\r\n";
    
    // Send email
    return mail($to_email, $subject, $message, $headers);
}
?>
