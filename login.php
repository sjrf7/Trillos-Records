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
        // LOGIN
        $email = trim($_POST['email']);
        $pass = $_POST['password'];

        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($pass, $user['password_hash'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['full_name'];
            $_SESSION['role'] = $user['role']; // Store role (admin/user)
            
            // Redirect based on role or just to index
            if ($user['role'] === 'admin') {
                // Optional: could redirect to admin.php directly, but user asked for panel access.
                // staying at index.php is fine generally.
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
            align-items: center;
            justify-content: center;
            overflow: hidden;
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
            /* Purple accent for contrast */
        }
    </style>
</head>

<body>
    <!-- Main Container -->
    <div class="w-full max-w-md p-6">

        <!-- Header / Logo Area -->
        <div class="text-center mb-8">
            <a href="index.html" class="inline-block mb-4 group">
                <div
                    class="w-16 h-16 rounded-full border-2 border-yellow-500/30 flex items-center justify-center mx-auto overflow-hidden group-hover:border-yellow-500 transition-colors">
                    <img src="./Nueva carpeta/Logo.png" alt="Trillos Logo" class="w-full h-full object-cover">
                </div>
            </a>
            <h2 class="brand-title text-3xl font-bold mb-2">Trillos Records</h2>
            <p class="text-gray-400 text-sm">Members Access</p>
        </div>

        <!-- Glass Card -->
        <div class="glass-card rounded-2xl p-8 relative overflow-hidden">

            <!-- Tabs -->
            <div class="flex justify-center space-x-12 mb-8 border-b border-gray-800 pb-2">
                <button onclick="switchTab('login')" id="tab-login"
                    class="tab-btn active text-lg font-medium pb-1">Iniciar Sesión</button>
                <button onclick="switchTab('register')" id="tab-register"
                    class="tab-btn text-lg font-medium pb-1">Registrarse</button>
            </div>

            <!-- Login Form -->
            <form id="login-form" class="visible-form" action="login.php" method="POST">
                <input type="hidden" name="action" value="login">
                <div class="input-group">
                    <input type="email" name="email" placeholder="Correo Electrónico" class="input-field" required>
                    <i data-lucide="mail" class="input-icon w-5 h-5"></i>
                </div>
                <div class="input-group">
                    <input type="password" name="password" placeholder="Contraseña" class="input-field" required>
                    <i data-lucide="lock" class="input-icon w-5 h-5"></i>
                </div>

                <div class="flex justify-between items-center mb-6 text-sm">
                    <label class="flex items-center text-gray-400 hover:text-white cursor-pointer">
                        <input type="checkbox" name="remember" class="mr-2 accent-yellow-500"> Recordarme
                    </label>
                    <a href="#" class="text-yellow-500 hover:underline">¿Olvidaste tu contraseña?</a>
                </div>

                <button type="submit" class="gold-btn w-full py-3 rounded-lg text-lg">
                    Entrar
                </button>
            </form>

            <!-- Register Form -->
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
                    <input type="password" name="password" placeholder="Contraseña" class="input-field" required>
                    <i data-lucide="lock" class="input-icon w-5 h-5"></i>
                </div>
                <div class="input-group">
                    <input type="password" name="confirm_password" placeholder="Confirmar Contraseña" class="input-field" required>
                    <i data-lucide="check-circle" class="input-icon w-5 h-5"></i>
                </div>

                <button type="submit" class="gold-btn w-full py-3 rounded-lg text-lg mb-4">
                    Crear Cuenta
                </button>

                <p class="text-center text-xs text-gray-400">
                    Al registrarte, aceptas nuestros <a href="#" class="text-yellow-500">Términos</a> y <a href="#"
                        class="text-yellow-500">Privacidad</a>.
                </p>
            </form>

            <div class="mt-6 text-center">
                <a href="index.php"
                    class="text-gray-500 hover:text-white text-sm flex items-center justify-center transition-colors">
                    <i data-lucide="arrow-left" class="w-4 h-4 mr-1"></i> Volver a Inicio
                </a>
            </div>

        </div>
    </div>

    <!-- Message Modal (Reused) -->
    <div id="msg-modal" class="fixed inset-0 bg-black bg-opacity-80 hidden flex items-center justify-center z-[200]">
        <div class="bg-gray-900 border border-yellow-600 p-6 rounded-xl shadow-2xl max-w-sm text-center">
            <i id="msg-icon" data-lucide="alert-circle" class="w-12 h-12 text-yellow-500 mx-auto mb-3"></i>
            <p id="msg-text" class="text-white text-lg mb-4"></p>
            <button onclick="closeMsg()"
                class="bg-yellow-500 hover:bg-yellow-600 text-black font-bold py-2 px-6 rounded-full">Ok</button>
        </div>
    </div>

    <script>
        lucide.createIcons();

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
    </script>
</body>

</html>