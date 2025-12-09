<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Perfil | Trillos Visual Records</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { background-color: #000; color: #fff; font-family: sans-serif; display: flex; align-items: center; justify-content: center; height: 100vh; }
    </style>
</head>
<body>
    <div class="text-center">
        <h1 class="text-4xl text-yellow-500 font-bold mb-4">Mi Perfil</h1>
        <p class="text-xl mb-8">Esta sección está en construcción.</p>
        <a href="index.php" class="text-yellow-600 hover:text-yellow-400">Volver al Inicio</a>
    </div>
</body>
</html>
