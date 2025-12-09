<?php
// Use mysqli directly to avoid PDO driver issues in CLI
$host = 'localhost';
$username = 'root';
$password = '';
$dbname = 'trillos_records';

$mysqli = new mysqli($host, $username, $password, $dbname);

if ($mysqli->connect_error) {
    die("Error de conexión: " . $mysqli->connect_error);
}

echo "Conectado a la base de datos (MySQLi).\n";

// 1. Create songs table
$sqlSongs = "CREATE TABLE IF NOT EXISTS songs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(150) NOT NULL,
    artist VARCHAR(150) NOT NULL,
    cover_path VARCHAR(255) NOT NULL,
    audio_path VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if ($mysqli->query($sqlSongs) === TRUE) {
    echo "Tabla 'songs' verificada/creada.\n";
} else {
    echo "Error creando tabla songs: " . $mysqli->error . "\n";
}

// 2. Create videos table
$sqlVideos = "CREATE TABLE IF NOT EXISTS videos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(150) NOT NULL,
    youtube_id VARCHAR(50) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if ($mysqli->query($sqlVideos) === TRUE) {
    echo "Tabla 'videos' verificada/creada.\n";
} else {
    echo "Error creando tabla videos: " . $mysqli->error . "\n";
}

// 3. Add column role to users if not exists
$result = $mysqli->query("SHOW COLUMNS FROM users LIKE 'role'");
if ($result->num_rows == 0) {
    $sqlAlter = "ALTER TABLE users ADD COLUMN role ENUM('user', 'admin') DEFAULT 'user'";
    if ($mysqli->query($sqlAlter) === TRUE) {
        echo "Columna 'role' añadida a 'users'.\n";
    } else {
        echo "Error añadiendo columna role: " . $mysqli->error . "\n";
    }
} else {
    echo "Columna 'role' ya existe en 'users'.\n";
}

// 4. Update first user to admin
if ($mysqli->query("UPDATE users SET role='admin' WHERE id=1") === TRUE) {
    echo "Primer usuario actualizado a rol 'admin'.\n";
} else {
    echo "Error actualizando admin: " . $mysqli->error . "\n";
}

$mysqli->close();
?>
