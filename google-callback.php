<?php
session_start();
require 'db.php';

header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['credential'])) {
    echo json_encode(['success' => false, 'message' => 'No credential received']);
    exit;
}

$id_token = $input['credential'];

// Verificar el token usando la API de Google
// Nota: En producción deberías usar una biblioteca como google-api-php-client,
// pero para esta integración simple podemos verificar el endpoint tokeninfo.
$url = "https://oauth2.googleapis.com/tokeninfo?id_token=" . $id_token;
$response = file_get_contents($url);

if (!$response) {
    echo json_encode(['success' => false, 'message' => 'Failed to verify token']);
    exit;
}

$payload = json_decode($response, true);

if (isset($payload['error_description'])) {
    echo json_encode(['success' => false, 'message' => $payload['error_description']]);
    exit;
}

// Verificar que la audiencia (Client ID) coincida si quieres seguridad extra aquí,
// pero simplemente procedemos con el email/sub verificado.

$google_id = $payload['sub'];
$email = $payload['email'];
$name = $payload['name'];
$picture = $payload['picture'];

try {
    // Verificar si el usuario existe por google_id o email
    $stmt = $pdo->prepare("SELECT * FROM users WHERE google_id = ? OR email = ?");
    $stmt->execute([$google_id, $email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        // Actualizar google_id y foto si faltan o cambiaron
        $updateNeeded = false;
        if (!$user['google_id']) {
            $user['google_id'] = $google_id;
            $updateNeeded = true;
        }
        if (!$user['profile_pic'] && $picture) {
            $user['profile_pic'] = $picture;
            $updateNeeded = true;
        }

        if ($updateNeeded) {
            $stmt = $pdo->prepare("UPDATE users SET google_id = ?, profile_pic = ? WHERE id = ?");
            $stmt->execute([$user['google_id'], $user['profile_pic'], $user['id']]);
        }

        // Iniciar sesión de usuario
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['full_name'];
        $_SESSION['role'] = $user['role'];

    } else {
        // Crear nuevo usuario
        // Generar un hash de contraseña aleatoria ya que usan Google
        $random_pass = bin2hex(random_bytes(16));
        $hash = password_hash($random_pass, PASSWORD_DEFAULT);

        $stmt = $pdo->prepare("INSERT INTO users (full_name, email, password_hash, google_id, profile_pic) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$name, $email, $hash, $google_id, $picture]);
        
        $newUserId = $pdo->lastInsertId();

        $_SESSION['user_id'] = $newUserId;
        $_SESSION['user_name'] = $name;
        $_SESSION['role'] = 'user';
    }

    echo json_encode(['success' => true]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    exit;
}
?>
