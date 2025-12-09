<?php
// migrate_web.php - Trigger migration from web browser
require 'db.php';

try {
    echo "<h1>Iniciando migración web...</h1>";

    // 1. Create songs table
    $sqlSongs = "CREATE TABLE IF NOT EXISTS songs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(150) NOT NULL,
        artist VARCHAR(150) NOT NULL,
        cover_path VARCHAR(255) NOT NULL,
        audio_path VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    $pdo->exec($sqlSongs);
    echo "<p>Tabla 'songs' verificada/creada.</p>";

    // 2. Create videos table
    $sqlVideos = "CREATE TABLE IF NOT EXISTS videos (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(150) NOT NULL,
        youtube_id VARCHAR(50) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    $pdo->exec($sqlVideos);
    echo "<p>Tabla 'videos' verificada/creada.</p>";

    // 3. Add column role to users
    $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'role'");
    $roleExists = $stmt->fetch();

    if (!$roleExists) {
        $sqlAlter = "ALTER TABLE users ADD COLUMN role ENUM('user', 'admin') DEFAULT 'user'";
        $pdo->exec($sqlAlter);
        echo "<p>Columna 'role' añadida a 'users'.</p>";
    } else {
        echo "<p>Columna 'role' ya existe.</p>";
    }

    // 4. Update admin
    $pdo->exec("UPDATE users SET role='admin' WHERE id=1");
    echo "<p>Primer usuario actualizado a admin.</p>";

    echo "<h2>¡Migración Completada!</h2>";

} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}
?>
