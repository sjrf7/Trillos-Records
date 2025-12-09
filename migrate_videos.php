<?php
require 'db.php';

try {
    // 1. Drop existing table (Warning: Data loss accepted as per plan)
    $pdo->exec("DROP TABLE IF EXISTS videos");
    echo "Old videos table dropped.\n";

    // 2. Re-create table with new schema
    $sql = "CREATE TABLE videos (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(150) NOT NULL,
        video_path VARCHAR(255) NOT NULL,
        cover_path VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    $pdo->exec($sql);
    echo "New videos table created successfully.\n";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
