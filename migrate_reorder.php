<?php
require 'db.php';

try {
    // Add position column to playlist_items if it doesn't exist
    $sql = "SHOW COLUMNS FROM playlist_items LIKE 'position'";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    
    if ($stmt->rowCount() == 0) {
        $pdo->exec("ALTER TABLE playlist_items ADD COLUMN position INT DEFAULT 0");
        echo "Columna 'position' añadida correctamente.\n";
        
        // Initialize position for existing items
        // We'll just set them by created_at order for each playlist
        $playlists = $pdo->query("SELECT id FROM playlists")->fetchAll(PDO::FETCH_COLUMN);
        
        foreach ($playlists as $pid) {
            $items = $pdo->prepare("SELECT id FROM playlist_items WHERE playlist_id = ? ORDER BY created_at ASC");
            $items->execute([$pid]);
            $rows = $items->fetchAll(PDO::FETCH_COLUMN);
            
            foreach ($rows as $index => $itemId) {
                $upd = $pdo->prepare("UPDATE playlist_items SET position = ? WHERE id = ?");
                $upd->execute([$index, $itemId]);
            }
        }
        echo "Posiciones iniciales asignadas.\n";
        
    } else {
        echo "La columna 'position' ya existe.\n";
    }

} catch (PDOException $e) {
    die("Error en la migración: " . $e->getMessage());
}
?>
