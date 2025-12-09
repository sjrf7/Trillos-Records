<?php
require 'db.php';

try {
    // 1. Add profile_pic to users if not exists
    $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'profile_pic'");
    if ($stmt->rowCount() == 0) {
        $pdo->exec("ALTER TABLE users ADD COLUMN profile_pic VARCHAR(255) DEFAULT 'uploads/default_profile.png'");
        echo "Added profile_pic column to users table.<br>";
    }

    // 2. Create playlists table
    $pdo->exec("CREATE TABLE IF NOT EXISTS playlists (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        name VARCHAR(100) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )");
    echo "Created playlists table.<br>";

    // 3. Create playlist_items table
    $pdo->exec("CREATE TABLE IF NOT EXISTS playlist_items (
        id INT AUTO_INCREMENT PRIMARY KEY,
        playlist_id INT NOT NULL,
        song_id INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (playlist_id) REFERENCES playlists(id) ON DELETE CASCADE,
        FOREIGN KEY (song_id) REFERENCES songs(id) ON DELETE CASCADE
    )");
    echo "Created playlist_items table.<br>";

    // 4. Create likes table
    $pdo->exec("CREATE TABLE IF NOT EXISTS likes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        type ENUM('song', 'video') NOT NULL,
        item_id INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        -- Note: Cannot FK item_id easily to two tables without complex polymorphism or triggers. 
        -- We'll manage integrity via app logic or separate tables. For now, simple ID reference.
    )");
    echo "Created likes table.<br>";

    // 5. Create history table
    $pdo->exec("CREATE TABLE IF NOT EXISTS history (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        type ENUM('song', 'video') NOT NULL,
        item_id INT NOT NULL,
        played_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )");
    echo "Created history table.<br>";

    echo "Migration completed successfully.";

} catch (PDOException $e) {
    die("Migration failed: " . $e->getMessage());
}
?>
