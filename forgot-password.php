<?php
session_start();
require 'db.php';
require 'email_sender.php';

$message = "";
$msg_type = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    
    if (empty($email)) {
        $message = "Por favor ingresa tu correo electrónico.";
        $msg_type = "error";
    } else {
        // Verificar si el correo existe
        $stmt = $pdo->prepare("SELECT id, full_name FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            // Generar token seguro
            $token = bin2hex(random_bytes(32));
            $expires_at = date('Y-m-d H:i:s', strtotime('+1 hour'));
            
            // Eliminar tokens antiguos para este correo
            $stmt = $pdo->prepare("DELETE FROM password_resets WHERE email = ?");
            $stmt->execute([$email]);
            
            // Insertar nuevo token
            $stmt = $pdo->prepare("INSERT INTO password_resets (email, token, expires_at) VALUES (?, ?, ?)");
            $stmt->execute([$email, $token, $expires_at]);
            
            // Crear enlace de restablecimiento
            $reset_link = "http://localhost/TrillosRecords/reset-password.php?token=" . $token;
            
            // Enviar correo
            if (sendPasswordResetEmail($email, $reset_link, $user['full_name'])) {
                $message = "Se ha enviado un enlace de recuperación a tu correo electrónico.";
                $msg_type = "success";
            } else {
                $message = "Error al enviar el correo. Por favor intenta más tarde.";
                $msg_type = "error";
            }
        } else {
            // No revelar si el correo existe o no (mejor práctica de seguridad)
            $message = "Si el correo existe, recibirás un enlace de recuperación.";
            $msg_type = "success";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar Contraseña | Trillos Visual Records</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@100..900&family=Playfair+Display:ital,wght@0,400..900;1,400..900&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        :root {
            --color-primary: #FFD700;
            --color-background: #000000;
            --color-text: #E5E5E5;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--color-background);
            color: var(--color-text);
            background: linear-gradient(-45deg, #000000, #1e0b2e, #000000, #2b2000);
            background-size: 400% 400%;
            animation: gradientBG 15s ease infinite;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            overflow-y: auto;
        }

        @keyframes gradientBG {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        .glass-card {
            background: rgba(15, 15, 15, 0.9);
            backdrop-filter: blur(25px);
            -webkit-backdrop-filter: blur(25px);
            border: 1px solid rgba(255, 215, 0, 0.15);
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.9), 0 0 30px rgba(255, 215, 0, 0.05);
        }

        .input-group {
            position: relative;
            margin-bottom: 1.5rem;
        }

        .input-field {
            width: 100%;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            padding: 1rem 1rem 1rem 3rem;
            border-radius: 0.75rem;
            color: white;
            outline: none;
            transition: all 0.3s ease;
        }

        .input-field:focus {
            border-color: var(--color-primary);
            box-shadow: 0 0 0 2px rgba(255, 215, 0, 0.2);
            background: rgba(255, 255, 255, 0.1);
        }

        .input-icon {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: rgba(255, 255, 255, 0.4);
            transition: color 0.3s ease;
        }

        .input-field:focus~.input-icon {
            color: var(--color-primary);
        }

        .gold-btn {
            background: linear-gradient(45deg, #FFD700, #FDB931);
            color: black;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(255, 215, 0, 0.3);
            cursor: pointer;
        }

        .gold-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(255, 215, 0, 0.5);
            filter: brightness(1.1);
        }

        .brand-title {
            font-family: 'Playfair Display', serif;
            color: var(--color-primary);
            text-shadow: 0 0 10px rgba(255, 215, 0, 0.4);
        }
    </style>
</head>

<body>
    <div class="w-full max-w-md p-6 my-auto">
        <!-- Encabezado -->
        <div class="text-center mb-8">
            <a href="login.php" class="inline-block mb-4 group">
                <div class="w-16 h-16 rounded-full border-2 border-yellow-500/30 flex items-center justify-center mx-auto overflow-hidden group-hover:border-yellow-500 transition-colors">
                    <img src="./assets/Logo.png" alt="Trillos Logo" class="w-full h-full object-cover">
                </div>
            </a>
            <h2 class="brand-title text-3xl font-bold mb-2">Recuperar Contraseña</h2>
            <p class="text-gray-400 text-sm">Ingresa tu correo para recibir el enlace</p>
        </div>

        <!-- Tarjeta de cristal -->
        <div class="glass-card rounded-2xl p-8 relative overflow-hidden">
            <form action="forgot-password.php" method="POST">
                <div class="input-group">
                    <input type="email" name="email" placeholder="Correo Electrónico" class="input-field" required>
                    <i data-lucide="mail" class="input-icon w-5 h-5"></i>
                </div>

                <button type="submit" class="gold-btn w-full py-3 rounded-lg text-lg mb-4">
                    Enviar Enlace
                </button>

                <div class="text-center">
                    <a href="login.php" class="text-gray-500 hover:text-white text-sm flex items-center justify-center transition-colors">
                        <i data-lucide="arrow-left" class="w-4 h-4 mr-1"></i> Volver al Login
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal de mensaje -->
    <div id="msg-modal" class="fixed inset-0 bg-black bg-opacity-80 hidden flex items-center justify-center z-[200]">
        <div class="bg-gray-900 border border-yellow-600 p-6 rounded-xl shadow-2xl max-w-sm text-center">
            <i id="msg-icon" data-lucide="alert-circle" class="w-12 h-12 text-yellow-500 mx-auto mb-3"></i>
            <p id="msg-text" class="text-white text-lg mb-4"></p>
            <button onclick="closeMsg()" class="bg-yellow-500 hover:bg-yellow-600 text-black font-bold py-2 px-6 rounded-full">Ok</button>
        </div>
    </div>

    <script>
        lucide.createIcons();

        function showMessage(msg, type = 'info') {
            document.getElementById('msg-text').textContent = msg;
            const icon = document.getElementById('msg-icon');
            if (type === 'error') {
                icon.setAttribute('data-lucide', 'x-circle');
                icon.classList.remove('text-yellow-500');
                icon.classList.add('text-red-500');
            } else if (type === 'success') {
                icon.setAttribute('data-lucide', 'check-circle-2');
                icon.classList.remove('text-red-500');
                icon.classList.add('text-yellow-500');
            }
            lucide.createIcons();
            document.getElementById('msg-modal').classList.remove('hidden');
        }

        function closeMsg() {
            document.getElementById('msg-modal').classList.add('hidden');
            <?php if ($msg_type === 'success'): ?>
            window.location.href = 'login.php';
            <?php endif; ?>
        }

        <?php if (!empty($message)): ?>
            showMessage("<?php echo addslashes($message); ?>", "<?php echo $msg_type; ?>");
        <?php endif; ?>
    </script>
</body>
</html>
