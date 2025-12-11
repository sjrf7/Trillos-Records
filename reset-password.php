<?php
session_start();
require 'db.php';

$message = "";
$msg_type = "";
$token = $_GET['token'] ?? '';
$valid_token = false;
$email = '';

// Validar token
if (!empty($token)) {
    $stmt = $pdo->prepare("SELECT email, expires_at FROM password_resets WHERE token = ?");
    $stmt->execute([$token]);
    $reset = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($reset) {
        if (strtotime($reset['expires_at']) > time()) {
            $valid_token = true;
            $email = $reset['email'];
        } else {
            $message = "Este enlace ha expirado. Solicita uno nuevo.";
            $msg_type = "error";
        }
    } else {
        $message = "Enlace inválido o ya utilizado.";
        $msg_type = "error";
    }
}

// Procesar restablecimiento de contraseña
if ($_SERVER["REQUEST_METHOD"] == "POST" && $valid_token) {
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    if (empty($new_password) || empty($confirm_password)) {
        $message = "Por favor completa todos los campos.";
        $msg_type = "error";
    } elseif ($new_password !== $confirm_password) {
        $message = "Las contraseñas no coinciden.";
        $msg_type = "error";
    } elseif (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*[^a-zA-Z0-9]).+$/', $new_password)) {
        $message = "La contraseña no cumple con los requisitos de seguridad.";
        $msg_type = "error";
    } else {
        // Actualizar contraseña
        $hash = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE email = ?");
        
        if ($stmt->execute([$hash, $email])) {
            // Eliminar token usado
            $pdo->prepare("DELETE FROM password_resets WHERE token = ?")->execute([$token]);
            
            $message = "¡Contraseña actualizada exitosamente! Ya puedes iniciar sesión.";
            $msg_type = "success";
            $valid_token = false; // Prevenir que el formulario se muestre de nuevo
        } else {
            $message = "Error al actualizar la contraseña.";
            $msg_type = "error";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Restablecer Contraseña | Trillos Visual Records</title>
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
            <h2 class="brand-title text-3xl font-bold mb-2">Nueva Contraseña</h2>
            <p class="text-gray-400 text-sm">Crea una contraseña segura</p>
        </div>

        <!-- Tarjeta de cristal -->
        <div class="glass-card rounded-2xl p-8 relative overflow-hidden">
            <?php if ($valid_token): ?>
            <form action="reset-password.php?token=<?php echo htmlspecialchars($token); ?>" method="POST">
                <div class="input-group">
                    <input type="password" name="new_password" id="new_password" placeholder="Nueva Contraseña" class="input-field" required>
                    <i data-lucide="lock" class="input-icon w-5 h-5"></i>
                </div>

                <!-- Requisitos de contraseña -->
                <div class="mb-4 text-xs text-gray-500 space-y-1 pl-1">
                    <p class="font-medium mb-1 text-gray-400">La contraseña debe tener:</p>
                    <div class="flex items-center space-x-2 req-item" id="req-lower">
                        <div class="w-1.5 h-1.5 rounded-full bg-gray-600 transition-colors"></div>
                        <span>Minúscula</span>
                    </div>
                    <div class="flex items-center space-x-2 req-item" id="req-upper">
                        <div class="w-1.5 h-1.5 rounded-full bg-gray-600 transition-colors"></div>
                        <span>Mayúscula</span>
                    </div>
                    <div class="flex items-center space-x-2 req-item" id="req-special">
                        <div class="w-1.5 h-1.5 rounded-full bg-gray-600 transition-colors"></div>
                        <span>Carácter especial (@$!%*?&)</span>
                    </div>
                </div>

                <div class="input-group">
                    <input type="password" name="confirm_password" placeholder="Confirmar Contraseña" class="input-field" required>
                    <i data-lucide="check-circle" class="input-icon w-5 h-5"></i>
                </div>

                <button type="submit" class="gold-btn w-full py-3 rounded-lg text-lg mb-4">
                    Restablecer Contraseña
                </button>
            </form>
            <?php else: ?>
            <div class="text-center py-8">
                <i data-lucide="alert-triangle" class="w-16 h-16 text-yellow-500 mx-auto mb-4"></i>
                <p class="text-gray-300 mb-6"><?php echo !empty($message) ? htmlspecialchars($message) : 'Enlace inválido o expirado.'; ?></p>
                <a href="forgot-password.php" class="gold-btn inline-block py-3 px-6 rounded-lg text-lg">
                    Solicitar Nuevo Enlace
                </a>
            </div>
            <?php endif; ?>

            <div class="text-center mt-4">
                <a href="login.php" class="text-gray-500 hover:text-white text-sm flex items-center justify-center transition-colors">
                    <i data-lucide="arrow-left" class="w-4 h-4 mr-1"></i> Volver al Login
                </a>
            </div>
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

        // Validación de contraseña
        const passwordInput = document.getElementById('new_password');
        if (passwordInput) {
            passwordInput.addEventListener('input', function() {
                const val = this.value;
                
                // Verificar minúsculas
                if (/[a-z]/.test(val)) {
                    setValid(document.getElementById('req-lower'));
                } else {
                    setInvalid(document.getElementById('req-lower'));
                }

                // Verificar mayúsculas
                if (/[A-Z]/.test(val)) {
                    setValid(document.getElementById('req-upper'));
                } else {
                    setInvalid(document.getElementById('req-upper'));
                }

                // Verificar caracteres especiales
                if (/[^A-Za-z0-9]/.test(val)) {
                    setValid(document.getElementById('req-special'));
                } else {
                    setInvalid(document.getElementById('req-special'));
                }
            });
        }

        function setValid(el) {
            const dot = el.querySelector('div');
            const text = el.querySelector('span');
            dot.classList.remove('bg-gray-600');
            dot.classList.add('bg-green-500', 'shadow-[0_0_5px_#22c55e]');
            text.classList.add('text-green-400');
            text.classList.remove('text-gray-500');
        }

        function setInvalid(el) {
            const dot = el.querySelector('div');
            const text = el.querySelector('span');
            dot.classList.add('bg-gray-600');
            dot.classList.remove('bg-green-500', 'shadow-[0_0_5px_#22c55e]');
            text.classList.remove('text-green-400');
            text.classList.add('text-gray-500');
        }

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

        <?php if (!empty($message) && !$valid_token): ?>
            showMessage("<?php echo addslashes($message); ?>", "<?php echo $msg_type; ?>");
        <?php elseif (!empty($message) && $msg_type === 'success'): ?>
            showMessage("<?php echo addslashes($message); ?>", "<?php echo $msg_type; ?>");
        <?php elseif (!empty($message)): ?>
            showMessage("<?php echo addslashes($message); ?>", "<?php echo $msg_type; ?>");
        <?php endif; ?>
    </script>
</body>
</html>
