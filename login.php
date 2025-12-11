<?php
session_start();
require 'db.php';

$message = "";
$msg_type = ""; // 'success' or 'error'

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['action']) && $_POST['action'] == 'register') {
        // REGISTRO
        $name = trim($_POST['full_name']);
        $email = trim($_POST['email']);
        $pass = $_POST['password'];
        $confirm = $_POST['confirm_password'];

        if ($pass !== $confirm) {
            $message = "Las contraseñas no coinciden.";
            $msg_type = "error";
        } elseif (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*[^a-zA-Z0-9]).+$/', $pass)) {
            $message = "La contraseña no cumple con los requisitos de seguridad.";
            $msg_type = "error";
        } else {
            // Verificar si el correo ya existe
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->rowCount() > 0) {
                $message = "Este correo ya está registrado.";
                $msg_type = "error";
            } else {
                // Insertar nuevo usuario
                $hash = password_hash($pass, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO users (full_name, email, password_hash) VALUES (?, ?, ?)");
                if ($stmt->execute([$name, $email, $hash])) {
                    $message = "¡Registro exitoso! Ahora puedes iniciar sesión.";
                    $msg_type = "success";
                } else {
                    $message = "Error al registrar. Inténtalo de nuevo.";
                    $msg_type = "error";
                }
            }
        }
    } elseif (isset($_POST['action']) && $_POST['action'] == 'login') {
        // INICIO DE SESIÓN
        $email = trim($_POST['email']);
        $pass = $_POST['password'];

        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($pass, $user['password_hash'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['full_name'];
            $_SESSION['role'] = $user['role']; // Almacenar rol (admin/usuario)
            
            // Redireccionar según el rol o simplemente al índice
            if ($user['role'] === 'admin') {
                // Opcional: podría redirigir a admin.php directamente, pero el usuario pidió acceso al panel.
                // quedarse en index.php está bien por lo general.
            }
            
            // Redireccionar al home
            header("Location: index.php");
            exit();
        } else {
            $message = "Correo o contraseña incorrectos.";
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
    <title>Acceso | Trillos Visual Records</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@100..900&family=Playfair+Display:ital,wght@0,400..900;1,400..900&display=swap"
        rel="stylesheet">
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
            /* Gradient dinámico y fluido */
            background: linear-gradient(-45deg, #000000, #1e0b2e, #000000, #2b2000);
            background-size: 400% 400%;
            animation: gradientBG 15s ease infinite;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            overflow-y: auto; /* Habilitar desplazamiento */
        }

        @keyframes gradientBG {
            0% {
                background-position: 0% 50%;
            }
            50% {
                background-position: 100% 50%;
            }
            100% {
                background-position: 0% 50%;
            }
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
            padding: 1rem 3rem 1rem 3rem;
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

        .tab-btn {
            position: relative;
            color: rgba(255, 255, 255, 0.5);
            transition: all 0.3s ease;
        }

        .tab-btn.active {
            color: var(--color-primary);
        }

        .tab-btn.active::after {
            content: '';
            position: absolute;
            bottom: -0.5rem;
            left: 0;
            width: 100%;
            height: 2px;
            background: var(--color-primary);
            box-shadow: 0 0 10px var(--color-primary);
        }

        .hidden-form {
            display: none;
            opacity: 0;
            transform: translateY(20px);
            transition: all 0.4s ease;
        }

        .visible-form {
            display: block;
            animation: fadeIn 0.5s forwards;
        }

        @keyframes fadeIn {
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Decoration circles */
        .orb {
            position: absolute;
            border-radius: 50%;
            filter: blur(80px);
            z-index: -1;
            opacity: 0.15;
        }

        .orb-1 {
            top: -10%;
            left: -10%;
            width: 400px;
            height: 400px;
            background: #FFD700;
        }

        .orb-2 {
            bottom: -10%;
            right: -10%;
            width: 300px;
            height: 300px;
            background: #800080;
            /* Acento púrpura para contraste */
        }
        /* Estilos de botón de Google personalizados */
        .google-wrapper {
            position: relative;
            width: 100%;
            height: 52px; /* Coincide con py-3 + tamaño de fuente aprox */
            border-radius: 0.75rem;
            overflow: hidden;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .google-wrapper:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 20px rgba(255, 255, 255, 0.15);
        }

        .g_id_signin {
            position: absolute !important;
            top: 0 !important;
            left: 0 !important;
            width: 100% !important;
            height: 100% !important;
            opacity: 0.01 !important;
            z-index: 20;
            overflow: hidden;
        }

        /* Asegurar que el iframe de Google cubra todo */
        .g_id_signin > div {
            width: 100% !important;
            height: 100% !important;
        }
        
        .g_id_signin iframe {
            width: 100% !important;
            height: 100% !important;
            transform: scale(1.1); /* Ligero Zoom para asegurar cobertura */
        }

        .google-custom-btn {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: #ffffff;
            color: #1f1f1f;
            display: flex;
            align-items: center;
            justify-content: center;
            border: none;
            z-index: 10;
        }
        
        .google-custom-btn span {
            font-family: 'Inter', sans-serif;
            font-weight: 600;
        }

        .password-toggle {
            position: absolute;
            right: 1rem;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: rgba(255, 255, 255, 0.5);
            z-index: 10;
            transition: color 0.3s ease;
        }

        .password-toggle:hover {
            color: var(--color-primary);
        }
    </style>
</head>

<body>
    <!-- Contenedor principal -->
    <div class="w-full max-w-md p-6 my-auto">

        <!-- Área de cabecera / logo -->
        <div class="text-center mb-8">
            <a href="index.php" class="inline-block mb-4 group">
                <div
                    class="w-16 h-16 rounded-full border-2 border-yellow-500/30 flex items-center justify-center mx-auto overflow-hidden group-hover:border-yellow-500 transition-colors">
                    <img src="./assets/Logo.png" alt="Trillos Logo" class="w-full h-full object-cover">
                </div>
            </a>
            <h2 class="brand-title text-3xl font-bold mb-2">Trillos Records</h2>
            <p class="text-gray-400 text-sm">Members Access</p>
        </div>

        <!-- Tarjeta de cristal -->
        <div class="glass-card rounded-2xl p-8 relative overflow-hidden">

            <!-- Pestañas -->
            <div class="flex justify-center space-x-12 mb-8 border-b border-gray-800 pb-2">
                <button onclick="switchTab('login')" id="tab-login"
                    class="tab-btn active text-lg font-medium pb-1">Iniciar Sesión</button>
                <button onclick="switchTab('register')" id="tab-register"
                    class="tab-btn text-lg font-medium pb-1">Registrarse</button>
            </div>

            <!-- Formulario de inicio de sesión -->
            <form id="login-form" class="visible-form" action="login.php" method="POST">
                <input type="hidden" name="action" value="login">
                <div class="input-group">
                    <input type="email" name="email" placeholder="Correo Electrónico" class="input-field" required>
                    <i data-lucide="mail" class="input-icon w-5 h-5"></i>
                </div>
                <div class="input-group">
                    <input type="password" name="password" id="login_password" placeholder="Contraseña" class="input-field" required>
                    <i data-lucide="lock" class="input-icon w-5 h-5"></i>
                    <i data-lucide="eye" class="password-toggle w-5 h-5" onclick="togglePassword('login_password', this)"></i>
                </div>

                <div class="flex justify-between items-center mb-6 text-sm">
                    <label class="flex items-center text-gray-400 hover:text-white cursor-pointer">
                        <input type="checkbox" name="remember" class="mr-2 accent-yellow-500"> Recordarme
                    </label>
                    <a href="forgot-password.php" class="text-yellow-500 hover:underline">¿Olvidaste tu contraseña?</a>
                </div>

                <button type="submit" class="gold-btn w-full py-3 rounded-lg text-lg">
                    Entrar
                </button>
            </form>

            <!-- Formulario de registro -->
            <form id="register-form" class="hidden-form" action="login.php" method="POST">
                <input type="hidden" name="action" value="register">
                <div class="input-group">
                    <input type="text" name="full_name" placeholder="Nombre Completo" class="input-field" required>
                    <i data-lucide="user" class="input-icon w-5 h-5"></i>
                </div>
                <div class="input-group">
                    <input type="email" name="email" placeholder="Correo Electrónico" class="input-field" required>
                    <i data-lucide="mail" class="input-icon w-5 h-5"></i>
                </div>
                <div class="input-group">
                    <input type="password" name="password" id="reg_password" placeholder="Contraseña" class="input-field" required>
                    <i data-lucide="lock" class="input-icon w-5 h-5"></i>
                    <i data-lucide="eye" class="password-toggle w-5 h-5" onclick="togglePassword('reg_password', this)"></i>
                </div>
                
                <!-- Requisitos de contraseña (Minimalista) -->
                <div class="mb-4 text-xs text-gray-500 space-y-1 pl-1 transition-all duration-300" id="password-reqs">
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
                    <input type="password" name="confirm_password" id="reg_confirm_password" placeholder="Confirmar Contraseña" class="input-field" required>
                    <i data-lucide="check-circle" class="input-icon w-5 h-5"></i>
                    <i data-lucide="eye" class="password-toggle w-5 h-5" onclick="togglePassword('reg_confirm_password', this)"></i>
                </div>

                <button type="submit" class="gold-btn w-full py-3 rounded-lg text-lg mb-4">
                    Crear Cuenta
                </button>

                <p class="text-center text-xs text-gray-400">
                    Al registrarte, aceptas nuestros <a href="#" class="text-yellow-500">Términos</a> y <a href="#"
                        class="text-yellow-500">Privacidad</a>.
                </p>
            </form>


            <!-- Sección de inicio de sesión de Google -->
            <div class="mt-8">
                <div class="relative mb-6">
                    <div class="absolute inset-0 flex items-center">
                        <div class="w-full border-t border-gray-700"></div>
                    </div>
                    <div class="relative flex justify-center text-sm">
                        <span class="px-3 bg-[#111111] text-gray-500 rounded-full border border-gray-800">O continúa con</span>
                    </div>
                </div>

                <div id="g_id_onload"
                    data-client_id="<?php echo htmlspecialchars(getenv('GOOGLE_CLIENT_ID')); ?>"
                    data-context="signin"
                    data-ux_mode="popup"
                    data-callback="handleCredentialResponse"
                    data-auto_prompt="false">
                </div>

                <div class="google-wrapper">
                    <!-- El botón invisible de Google capturando clics -->
                    <div class="g_id_signin"
                        data-type="standard"
                        data-shape="rectangular"
                        data-theme="filled_black"
                        data-text="signin_with"
                        data-size="large"
                        data-logo_alignment="left"
                        data-width="400">
                    </div>
                    
                    <!-- El botón moderno personalizado -->
                    <button type="button" class="google-custom-btn w-full py-3 rounded-xl flex items-center justify-center space-x-3 transition-all">
                        <svg class="w-6 h-6" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/>
                            <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
                            <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.84z" fill="#FBBC05"/>
                            <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/>
                        </svg>
                        <span class="font-medium text-lg text-gray-800">Google</span>
                    </button>
                </div>
            </div>

            <div class="mt-6 text-center">
                <a href="index.php"
                    class="text-gray-500 hover:text-white text-sm flex items-center justify-center transition-colors">
                    <i data-lucide="arrow-left" class="w-4 h-4 mr-1"></i> Volver a Inicio
                </a>
            </div>

        </div>
    </div>

    <!-- Modal de mensaje (Reutilizado) -->
    <div id="msg-modal" class="fixed inset-0 bg-black bg-opacity-80 hidden flex items-center justify-center z-[200]">
        <div class="bg-gray-900 border border-yellow-600 p-6 rounded-xl shadow-2xl max-w-sm text-center">
            <i id="msg-icon" data-lucide="alert-circle" class="w-12 h-12 text-yellow-500 mx-auto mb-3"></i>
            <p id="msg-text" class="text-white text-lg mb-4"></p>
            <button onclick="closeMsg()"
                class="bg-yellow-500 hover:bg-yellow-600 text-black font-bold py-2 px-6 rounded-full">Ok</button>
        </div>
    </div>

    <!-- Servicios de identidad de Google -->
    <script src="https://accounts.google.com/gsi/client" async defer></script>
    <script>
        lucide.createIcons();

        function handleCredentialResponse(response) {
            // Enviar la credencial a tu backend
            fetch('google-callback.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ credential: response.credential })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    window.location.href = 'index.php';
                } else {
                    showMessage('Error al iniciar con Google: ' + data.message, 'error');
                }
            })
            .catch(err => {
                showMessage('Error de conexión', 'error');
            });
        }

        function switchTab(tab) {
            const loginForm = document.getElementById('login-form');
            const registerForm = document.getElementById('register-form');
            const loginBtn = document.getElementById('tab-login');
            const registerBtn = document.getElementById('tab-register');

            if (tab === 'login') {
                loginForm.classList.replace('hidden-form', 'visible-form');
                registerForm.classList.replace('visible-form', 'hidden-form');
                loginBtn.classList.add('active');
                registerBtn.classList.remove('active');
            } else {
                registerForm.classList.replace('hidden-form', 'visible-form');
                loginForm.classList.replace('visible-form', 'hidden-form');
                registerBtn.classList.add('active');
                loginBtn.classList.remove('active');
            }
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
        }

        // Mostrar mensajes de PHP si existen
        <?php if (!empty($message)): ?>
            showMessage("<?php echo addslashes($message); ?>", "<?php echo $msg_type; ?>");
        <?php endif; ?>

        // Lógica de validación de contraseña
        document.addEventListener('DOMContentLoaded', () => {
            const passwordInput = document.getElementById('reg_password');
            const reqLower = document.getElementById('req-lower');
            const reqUpper = document.getElementById('req-upper');
            const reqSpecial = document.getElementById('req-special');

            if (passwordInput) {
                passwordInput.addEventListener('input', function() {
                    const val = this.value;
                    
                    // Verificar minúsculas
                    if (/[a-z]/.test(val)) {
                        setValid(reqLower);
                    } else {
                        setInvalid(reqLower);
                    }

                    // Verificar mayúsculas
                    if (/[A-Z]/.test(val)) {
                        setValid(reqUpper);
                    } else {
                        setInvalid(reqUpper);
                    }

                    // Verificar caracteres especiales
                    if (/[^A-Za-z0-9]/.test(val)) {
                        setValid(reqSpecial);
                    } else {
                        setInvalid(reqSpecial);
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
        });

        function togglePassword(inputId, toggleIcon) {
            const input = document.getElementById(inputId);
            if (input.type === 'password') {
                input.type = 'text';
                toggleIcon.setAttribute('data-lucide', 'eye-off');
            } else {
                input.type = 'password';
                toggleIcon.setAttribute('data-lucide', 'eye');
            }
            lucide.createIcons();
        }
    </script>
</body>

</html>