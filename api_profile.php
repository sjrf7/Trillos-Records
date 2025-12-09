<?php
session_start();
require 'db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$user_id = $_SESSION['user_id'];
$action = $_POST['action'] ?? $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'get_stats':
            // Count playlists
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM playlists WHERE user_id = ?");
            $stmt->execute([$user_id]);
            $playlists_count = $stmt->fetchColumn();

            // Count likes (songs + videos)
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM likes WHERE user_id = ?");
            $stmt->execute([$user_id]);
            $likes_count = $stmt->fetchColumn();

            // Get user info
            $stmt = $pdo->prepare("SELECT full_name, profile_pic, google_id FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            // Validate profile_pic
            if ($user && $user['profile_pic']) {
                $pic = $user['profile_pic'];
                // Check if it's a URL or a valid local file
                if (!filter_var($pic, FILTER_VALIDATE_URL) && !file_exists($pic)) {
                    $user['profile_pic'] = null;
                }
            }

            echo json_encode([
                'success' => true,
                'stats' => [
                    'playlists' => $playlists_count,
                    'likes' => $likes_count
                ],
                'user' => $user
            ]);
            break;

        case 'update_profile':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') throw new Exception('Invalid method');
            
            $full_name = trim($_POST['full_name'] ?? '');
            if (empty($full_name)) throw new Exception('El nombre no puede estar vacío');

            // Handle Profile Pic Upload
            $profile_pic_path = null;
            if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = 'uploads/profiles/';
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
                
                $fileExt = strtolower(pathinfo($_FILES['profile_pic']['name'], PATHINFO_EXTENSION));
                $allowed = ['jpg', 'jpeg', 'png', 'gif'];
                
                if (!in_array($fileExt, $allowed)) throw new Exception('Formato de imagen no válido');
                
                $fileName = uniqid('profile_', true) . '.' . $fileExt;
                $targetFile = $uploadDir . $fileName;
                
                if (move_uploaded_file($_FILES['profile_pic']['tmp_name'], $targetFile)) {
                    $profile_pic_path = $targetFile;
                }
            }

            // Update Query
            if ($profile_pic_path) {
                $stmt = $pdo->prepare("UPDATE users SET full_name = ?, profile_pic = ? WHERE id = ?");
                $stmt->execute([$full_name, $profile_pic_path, $user_id]);
            } else {
                $stmt = $pdo->prepare("UPDATE users SET full_name = ? WHERE id = ?");
                $stmt->execute([$full_name, $user_id]);
            }

            echo json_encode(['success' => true, 'message' => 'Perfil actualizado']);
            break;

        case 'create_playlist':
            $name = trim($_POST['name'] ?? '');
            if (empty($name)) throw new Exception('El nombre de la lista no puede estar vacío');
            
            $stmt = $pdo->prepare("INSERT INTO playlists (user_id, name) VALUES (?, ?)");
            $stmt->execute([$user_id, $name]);
            
            echo json_encode(['success' => true, 'message' => 'Lista creada']);
            break;

        case 'delete_playlist':
            $playlist_id = $_POST['playlist_id'] ?? 0;
            
            // Verify ownership
            $stmt = $pdo->prepare("SELECT id FROM playlists WHERE id = ? AND user_id = ?");
            $stmt->execute([$playlist_id, $user_id]);
            if (!$stmt->fetch()) throw new Exception('Lista no encontrada o permiso denegado');
            
            // Delete items first (safe approach)
            $stmt = $pdo->prepare("DELETE FROM playlist_items WHERE playlist_id = ?");
            $stmt->execute([$playlist_id]);
            
            // Delete playlist
            $stmt = $pdo->prepare("DELETE FROM playlists WHERE id = ?");
            $stmt->execute([$playlist_id]);
            
            echo json_encode(['success' => true, 'message' => 'Lista eliminada']);
            break;

        case 'rename_playlist':
            $playlist_id = $_POST['playlist_id'] ?? 0;
            $name = trim($_POST['name'] ?? '');
            
            if (empty($name)) throw new Exception('El nombre no puede estar vacío');
            
            // Verify ownership and update
            $stmt = $pdo->prepare("UPDATE playlists SET name = ? WHERE id = ? AND user_id = ?");
            $result = $stmt->execute([$name, $playlist_id, $user_id]);
            
            if ($stmt->rowCount() === 0) {
                 // Check if it was because ID/User mismatch or just same name
                 // But for UI feedback, success is fine if it exists. 
                 // Let's strict check ownership if needed, but rowCount 0 is fine if name unchanged.
                 // Ideally verify ownership first to distinguish.
                 $check = $pdo->prepare("SELECT id FROM playlists WHERE id = ? AND user_id = ?");
                 $check->execute([$playlist_id, $user_id]);
                 if (!$check->fetch()) throw new Exception('Lista no encontrada o permiso denegado');
            }
            
            echo json_encode(['success' => true, 'message' => 'Nombre actualizado']);
            break;

        case 'toggle_like':
            $type = $_POST['type'] ?? ''; // 'song' or 'video'
            $item_id = $_POST['item_id'] ?? 0;
            
            if (!in_array($type, ['song', 'video'])) throw new Exception('Tipo inválido');
            
            // Check if already liked
            $stmt = $pdo->prepare("SELECT id FROM likes WHERE user_id = ? AND type = ? AND item_id = ?");
            $stmt->execute([$user_id, $type, $item_id]);
            $existing = $stmt->fetch();
            
            if ($existing) {
                // Unlike
                $pdo->prepare("DELETE FROM likes WHERE id = ?")->execute([$existing['id']]);
                echo json_encode(['success' => true, 'liked' => false]);
            } else {
                // Like
                $stmt = $pdo->prepare("INSERT INTO likes (user_id, type, item_id) VALUES (?, ?, ?)");
                $stmt->execute([$user_id, $type, $item_id]);
                echo json_encode(['success' => true, 'liked' => true]);
            }
            break;

        case 'add_history':
            $type = $_POST['type'] ?? '';
            $item_id = $_POST['item_id'] ?? 0;
            
            if (!in_array($type, ['song', 'video'])) throw new Exception('Tipo inválido');

            // Optional: Limit history size per user? Or just insert.
            $stmt = $pdo->prepare("INSERT INTO history (user_id, type, item_id, played_at) VALUES (?, ?, ?, NOW())");
            $stmt->execute([$user_id, $type, $item_id]);
            
            echo json_encode(['success' => true]);
            break;

        case 'get_likes':
             // Fetch liked songs
             $stmt = $pdo->prepare("
                SELECT s.*, 'song' as type 
                FROM likes l 
                JOIN songs s ON l.item_id = s.id 
                WHERE l.user_id = ? AND l.type = 'song'
                ORDER BY l.created_at DESC
            ");
            $stmt->execute([$user_id]);
            $liked_songs = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Fetch liked videos
            $stmt = $pdo->prepare("
                SELECT v.*, 'video' as type 
                FROM likes l 
                JOIN videos v ON l.item_id = v.id 
                WHERE l.user_id = ? AND l.type = 'video'
                ORDER BY l.created_at DESC
            ");
            $stmt->execute([$user_id]);
            $liked_videos = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode(['success' => true, 'songs' => $liked_songs, 'videos' => $liked_videos]);
            break;
            
        case 'get_history':
            // Fetch history with item details
            // Complex query or two queries. Let's do two for simplicity and join manually or just return raw and fetch details.
            // Better: Union or just separate lists. Let's return mixed list ordered by date.
            
            // Songs history
            $stmt = $pdo->prepare("
                SELECT h.played_at, s.*, 'song' as item_type
                FROM history h
                JOIN songs s ON h.item_id = s.id
                WHERE h.user_id = ? AND h.type = 'song'
                ORDER BY h.played_at DESC LIMIT 10
            ");
            $stmt->execute([$user_id]);
            $level1 = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Videos history
            $stmt = $pdo->prepare("
                SELECT h.played_at, v.*, 'video' as item_type
                FROM history h
                JOIN videos v ON h.item_id = v.id
                WHERE h.user_id = ? AND h.type = 'video'
                ORDER BY h.played_at DESC LIMIT 10
            ");
            $stmt->execute([$user_id]);
            $level2 = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $history = array_merge($level1, $level2);
            
            // Sort by played_at desc
            usort($history, function($a, $b) {
                return strtotime($b['played_at']) - strtotime($a['played_at']);
            });

            echo json_encode(['success' => true, 'history' => array_slice($history, 0, 10)]); // Limit to 10
            break;
            
        case 'get_playlists':
            $stmt = $pdo->prepare("SELECT * FROM playlists WHERE user_id = ? ORDER BY created_at DESC");
            $stmt->execute([$user_id]);
            $playlists = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['success' => true, 'playlists' => $playlists]);
            break;

        case 'get_playlist_details':
            $playlist_id = $_POST['playlist_id'] ?? 0;
            
            // Get playlist info
            $stmt = $pdo->prepare("SELECT * FROM playlists WHERE id = ? AND user_id = ?");
            $stmt->execute([$playlist_id, $user_id]);
            $playlist = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$playlist) throw new Exception('Lista no encontrada');

            // Get items
            $stmt = $pdo->prepare("
                SELECT pi.id as item_id, s.*, pi.created_at as added_at
                FROM playlist_items pi
                JOIN songs s ON pi.song_id = s.id
                WHERE pi.playlist_id = ?
                ORDER BY pi.position ASC, pi.created_at DESC
            ");
            $stmt->execute([$playlist_id]);
            $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode(['success' => true, 'playlist' => $playlist, 'items' => $items]);
            break;

        case 'add_playlist_item':
            $playlist_id = $_POST['playlist_id'] ?? 0;
            $song_id = $_POST['song_id'] ?? 0;

            // Verify ownership
            $stmt = $pdo->prepare("SELECT id FROM playlists WHERE id = ? AND user_id = ?");
            $stmt->execute([$playlist_id, $user_id]);
            if (!$stmt->fetch()) throw new Exception('Lista no encontrada');

            // Check if already exists
            $stmt = $pdo->prepare("SELECT id FROM playlist_items WHERE playlist_id = ? AND song_id = ?");
            $stmt->execute([$playlist_id, $song_id]);
            if ($stmt->fetch()) throw new Exception('La canción ya está en la lista');

            // Get max position
            $stmt = $pdo->prepare("SELECT MAX(position) FROM playlist_items WHERE playlist_id = ?");
            $stmt->execute([$playlist_id]);
            $maxPos = $stmt->fetchColumn();
            $newPos = is_numeric($maxPos) ? $maxPos + 1 : 0;

            $stmt = $pdo->prepare("INSERT INTO playlist_items (playlist_id, song_id, position) VALUES (?, ?, ?)");
            $stmt->execute([$playlist_id, $song_id, $newPos]);

            echo json_encode(['success' => true, 'message' => 'Canción añadida']);
            break;

        case 'reorder_playlist':
            $playlist_id = $_POST['playlist_id'] ?? 0;
            $items = $_POST['items'] ?? []; // Array of playlist_item_ids (NOT song_ids) or song_ids? 
            // The frontend should send song_ids in order or reference IDs. 
            // The `get_playlist_details` returns `item_id` (PK of playlist_items). Let's use that.
            
            // Verify ownership
            $stmt = $pdo->prepare("SELECT id FROM playlists WHERE id = ? AND user_id = ?");
            $stmt->execute([$playlist_id, $user_id]);
            if (!$stmt->fetch()) throw new Exception('Lista no encontrada');
            
            if (!empty($items) && is_array($items)) {
                $pdo->beginTransaction();
                try {
                    $sql = "UPDATE playlist_items SET position = ? WHERE id = ? AND playlist_id = ?";
                    $stmt = $pdo->prepare($sql);
                    foreach ($items as $index => $itemId) {
                         $stmt->execute([$index, $itemId, $playlist_id]);
                    }
                    $pdo->commit();
                    echo json_encode(['success' => true]);
                } catch (Exception $e) {
                    $pdo->rollBack();
                    throw $e;
                }
            } else {
                echo json_encode(['success' => false, 'message' => 'No items provided']);
            }
            break;

        case 'get_all_songs':
            $stmt = $pdo->query("SELECT id, title, artist, cover_path FROM songs ORDER BY title ASC");
            $songs = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['success' => true, 'songs' => $songs]);
            break;

        case 'change_password':
            $current_password = $_POST['current_password'] ?? '';
            $new_password = $_POST['new_password'] ?? '';
            
            // Validate inputs
            if (empty($new_password)) {
                throw new Exception('La nueva contraseña es requerida');
            }
            
            // Validate new password complexity
            if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*[^a-zA-Z0-9]).+$/', $new_password)) {
                throw new Exception('La contraseña no cumple con los requisitos de seguridad');
            }
            
            // Get current password hash and google_id
            $stmt = $pdo->prepare("SELECT password_hash, google_id FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$user) {
                throw new Exception('Usuario no encontrado');
            }

            // Verify current password ONLY if not a Google user (or if they provided it)
            // If user has google_id and empty current_password, we skip check.
            $isGoogleUser = !empty($user['google_id']);
            
            if (!$isGoogleUser) {
                if (empty($current_password)) throw new Exception('La contraseña actual es requerida');
                if (!password_verify($current_password, $user['password_hash'])) {
                    throw new Exception('La contraseña actual es incorrecta');
                }
            } else {
                // If is GoogleUser but provided a password, verify it just in case? 
                // Plan says: "skip... if user only has Google login". 
                // Let's allow skipping if they ARE a google user.
                if (!empty($current_password) && !password_verify($current_password, $user['password_hash'])) {
                     throw new Exception('La contraseña actual es incorrecta');
                }
            }
            
            // Hash new password
            $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
            
            // Update password
            $stmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
            $stmt->execute([$new_hash, $user_id]);
            
            echo json_encode(['success' => true, 'message' => 'Contraseña actualizada correctamente']);
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
