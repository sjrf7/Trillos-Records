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
            // Contar listas de reproducción
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM playlists WHERE user_id = ?");
            $stmt->execute([$user_id]);
            $playlists_count = $stmt->fetchColumn();

            // Contar 'Me gusta' (canciones + videos)
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM likes WHERE user_id = ?");
            $stmt->execute([$user_id]);
            $likes_count = $stmt->fetchColumn();

            // Obtener información del usuario
            $stmt = $pdo->prepare("SELECT full_name, profile_pic, google_id FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            // Validar foto de perfil
            if ($user && $user['profile_pic']) {
                $pic = $user['profile_pic'];
                // Verificar si es una URL o un archivo local válido
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

            // Manejar subida de foto de perfil
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

            // Consulta de actualización
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
            
            // Verificar propiedad
            $stmt = $pdo->prepare("SELECT id FROM playlists WHERE id = ? AND user_id = ?");
            $stmt->execute([$playlist_id, $user_id]);
            if (!$stmt->fetch()) throw new Exception('Lista no encontrada o permiso denegado');
            
            // Eliminar elementos primero (enfoque seguro)
            $stmt = $pdo->prepare("DELETE FROM playlist_items WHERE playlist_id = ?");
            $stmt->execute([$playlist_id]);
            
            // Eliminar lista de reproducción
            $stmt = $pdo->prepare("DELETE FROM playlists WHERE id = ?");
            $stmt->execute([$playlist_id]);
            
            echo json_encode(['success' => true, 'message' => 'Lista eliminada']);
            break;

        case 'rename_playlist':
            $playlist_id = $_POST['playlist_id'] ?? 0;
            $name = trim($_POST['name'] ?? '');
            
            if (empty($name)) throw new Exception('El nombre no puede estar vacío');
            
            // Verificar propiedad y actualizar
            $stmt = $pdo->prepare("UPDATE playlists SET name = ? WHERE id = ? AND user_id = ?");
            $result = $stmt->execute([$name, $playlist_id, $user_id]);
            
            if ($stmt->rowCount() === 0) {
                 // Verificar si fue por discrepancia de ID/Usuario o simplemente el mismo nombre
                 // Pero para retroalimentación de UI, éxito está bien si existe.
                 // Verifiquemos propiedad estrictamente si es necesario, pero rowCount 0 está bien si el nombre no cambia.
                 // Idealmente verificar propiedad primero para distinguir.
                 $check = $pdo->prepare("SELECT id FROM playlists WHERE id = ? AND user_id = ?");
                 $check->execute([$playlist_id, $user_id]);
                 if (!$check->fetch()) throw new Exception('Lista no encontrada o permiso denegado');
            }
            
            echo json_encode(['success' => true, 'message' => 'Nombre actualizado']);
            break;

        case 'toggle_like':
            $type = $_POST['type'] ?? ''; // 'cancion' o 'video'
            $item_id = $_POST['item_id'] ?? 0;
            
            if (!in_array($type, ['song', 'video'])) throw new Exception('Tipo inválido');
            
            // Verificar si ya se dio 'Me gusta'
            $stmt = $pdo->prepare("SELECT id FROM likes WHERE user_id = ? AND type = ? AND item_id = ?");
            $stmt->execute([$user_id, $type, $item_id]);
            $existing = $stmt->fetch();
            
            if ($existing) {
                // Quitar 'Me gusta'
                $pdo->prepare("DELETE FROM likes WHERE id = ?")->execute([$existing['id']]);
                echo json_encode(['success' => true, 'liked' => false]);
            } else {
                // Dar 'Me gusta'
                $stmt = $pdo->prepare("INSERT INTO likes (user_id, type, item_id) VALUES (?, ?, ?)");
                $stmt->execute([$user_id, $type, $item_id]);
                echo json_encode(['success' => true, 'liked' => true]);
            }
            break;

        case 'add_history':
            $type = $_POST['type'] ?? '';
            $item_id = $_POST['item_id'] ?? 0;
            
            if (!in_array($type, ['song', 'video'])) throw new Exception('Tipo inválido');

            // Opcional: ¿Limitar tamaño del historial por usuario? O simplemente insertar.
            $stmt = $pdo->prepare("INSERT INTO history (user_id, type, item_id, played_at) VALUES (?, ?, ?, NOW())");
            $stmt->execute([$user_id, $type, $item_id]);
            
            echo json_encode(['success' => true]);
            break;

        case 'get_likes':
             // Obtener canciones que gustan
             $stmt = $pdo->prepare("
                SELECT s.*, 'song' as type 
                FROM likes l 
                JOIN songs s ON l.item_id = s.id 
                WHERE l.user_id = ? AND l.type = 'song'
                ORDER BY l.created_at DESC
            ");
            $stmt->execute([$user_id]);
            $liked_songs = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Obtener videos que gustan
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
            // Obtener historial con detalles de elementos
            // Consulta compleja o dos consultas. Hagamos dos por simplicidad y unamos manualmente o simplemente devolvamos crudo y obtengamos detalles.
            // Mejor: Unión o listas separadas. Devolvamos lista mixta ordenada por fecha.
            
            // Historial de canciones
            $stmt = $pdo->prepare("
                SELECT h.played_at, s.*, 'song' as item_type
                FROM history h
                JOIN songs s ON h.item_id = s.id
                WHERE h.user_id = ? AND h.type = 'song'
                ORDER BY h.played_at DESC LIMIT 10
            ");
            $stmt->execute([$user_id]);
            $level1 = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Historial de videos
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
            
            // Ordenar por played_at descendente
            usort($history, function($a, $b) {
                return strtotime($b['played_at']) - strtotime($a['played_at']);
            });

            echo json_encode(['success' => true, 'history' => array_slice($history, 0, 10)]); // Limitar a 10
            break;
            
        case 'get_playlists':
            $stmt = $pdo->prepare("SELECT * FROM playlists WHERE user_id = ? ORDER BY created_at DESC");
            $stmt->execute([$user_id]);
            $playlists = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['success' => true, 'playlists' => $playlists]);
            break;

        case 'get_playlist_details':
            $playlist_id = $_POST['playlist_id'] ?? 0;
            
            // Obtener información de lista de reproducción
            $stmt = $pdo->prepare("SELECT * FROM playlists WHERE id = ? AND user_id = ?");
            $stmt->execute([$playlist_id, $user_id]);
            $playlist = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$playlist) throw new Exception('Lista no encontrada');

            // Obtener elementos
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

            // Verificar propiedad
            $stmt = $pdo->prepare("SELECT id FROM playlists WHERE id = ? AND user_id = ?");
            $stmt->execute([$playlist_id, $user_id]);
            if (!$stmt->fetch()) throw new Exception('Lista no encontrada');

            // Verificar si ya existe
            $stmt = $pdo->prepare("SELECT id FROM playlist_items WHERE playlist_id = ? AND song_id = ?");
            $stmt->execute([$playlist_id, $song_id]);
            if ($stmt->fetch()) throw new Exception('La canción ya está en la lista');

            // Obtener posición máxima
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
            $items = $_POST['items'] ?? []; // ¿Array de playlist_item_ids (NO song_ids) o song_ids?
            // El frontend debería enviar song_ids en orden o IDs de referencia.
            // `get_playlist_details` devuelve `item_id` (PK de playlist_items). Usemos eso.
            
            // Verificar propiedad
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
            
            // Validar entradas
            if (empty($new_password)) {
                throw new Exception('La nueva contraseña es requerida');
            }
            
            // Validar complejidad de nueva contraseña
            if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*[^a-zA-Z0-9]).+$/', $new_password)) {
                throw new Exception('La contraseña no cumple con los requisitos de seguridad');
            }
            
            // Obtener hash de contraseña actual y google_id
            $stmt = $pdo->prepare("SELECT password_hash, google_id FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$user) {
                throw new Exception('Usuario no encontrado');
            }

            // Verificar contraseña actual SOLO si no es usuario de Google (o si la proporcionó)
            // Si el usuario tiene google_id y current_password vacío, omitimos verificación.
            $isGoogleUser = !empty($user['google_id']);
            
            if (!$isGoogleUser) {
                if (empty($current_password)) throw new Exception('La contraseña actual es requerida');
                if (!password_verify($current_password, $user['password_hash'])) {
                    throw new Exception('La contraseña actual es incorrecta');
                }
            } else {
                // ¿Si es GoogleUser pero proporcionó una contraseña, verificarla por si acaso?
                // El plan dice: "omitir... si el usuario solo tiene inicio de sesión de Google".
                // Permitamos omitir si SON usuarios de Google.
                if (!empty($current_password) && !password_verify($current_password, $user['password_hash'])) {
                     throw new Exception('La contraseña actual es incorrecta');
                }
            }
            
            // Hash de nueva contraseña
            $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
            
            // Actualizar contraseña
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
