<?php
session_start();
require 'db.php';

// Access Control
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit();
}

$message = '';
$error = '';

// Check for AJAX/Fetch (Send JSON)
$isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

function jsonResponse($success, $msg) {
    global $isAjax;
    if ($isAjax) {
        header('Content-Type: application/json');
        echo json_encode(['success' => $success, 'message' => $msg]);
        exit();
    }
}



// Handle Song Upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'upload_song') {
    $title = trim($_POST['title']);
    $artist = trim($_POST['artist']);
    
    // File handling
    $cover = $_FILES['cover'];
    $audio = $_FILES['audio'];
    
    if ($cover['error'] === 0 && $audio['error'] === 0) {
        $uploadDir = 'uploads/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
        
        // Generate unique names
        $coverName = uniqid() . '_' . basename($cover['name']);
        $audioName = uniqid() . '_' . basename($audio['name']);
        
        $coverPath = $uploadDir . $coverName;
        $audioPath = $uploadDir . $audioName;
        
        if (move_uploaded_file($cover['tmp_name'], $coverPath) && move_uploaded_file($audio['tmp_name'], $audioPath)) {
            $stmt = $pdo->prepare("INSERT INTO songs (title, artist, cover_path, audio_path) VALUES (?, ?, ?, ?)");
            if ($stmt->execute([$title, $artist, $coverPath, $audioPath])) {
                $message = "Canción subida exitosamente.";
                jsonResponse(true, $message);
            } else {
                $error = "Error al guardar en base de datos.";
                jsonResponse(false, $error);
            }
        } else {
            $error = "Error al mover archivos al servidor.";
            jsonResponse(false, $error);
        }
    } else {
        $error = "Error en la subida de archivos.";
        jsonResponse(false, $error);
    }
}

// Handle Video Add (Local Upload)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_video') {
    $title = trim($_POST['title']);
    
    // File handling
    $video = $_FILES['video_file'];
    $cover = $_FILES['video_cover'];
    
    if ($video['error'] === 0 && $cover['error'] === 0) {
        $uploadDir = 'uploads/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
        
        // Generate unique names
        $videoName = uniqid() . '_vid_' . basename($video['name']);
        $coverName = uniqid() . '_thumb_' . basename($cover['name']);
        
        $videoPath = $uploadDir . $videoName;
        $coverPath = $uploadDir . $coverName;
        
        if (move_uploaded_file($video['tmp_name'], $videoPath) && move_uploaded_file($cover['tmp_name'], $coverPath)) {
            $stmt = $pdo->prepare("INSERT INTO videos (title, video_path, cover_path) VALUES (?, ?, ?)");
            if ($stmt->execute([$title, $videoPath, $coverPath])) {
                $message = "Video subido exitosamente.";
                jsonResponse(true, $message);
            } else {
                $error = "Error al guardar en la base de datos.";
                jsonResponse(false, $error);
            }
        } else {
            $error = "Error al mover los archivos al servidor.";
            jsonResponse(false, $error);
        }
    } else {
        $error = "Error: Debes subir un video y una miniatura.";
        jsonResponse(false, $error);
    }
}

// Handle Song Deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_song') {
    $id = $_POST['id'];
    
    // Fetch paths
    $stmt = $pdo->prepare("SELECT cover_path, audio_path FROM songs WHERE id = ?");
    $stmt->execute([$id]);
    $song = $stmt->fetch();
    
    if ($song) {
        // Delete files
        if (file_exists($song['cover_path'])) unlink($song['cover_path']);
        if (file_exists($song['audio_path'])) unlink($song['audio_path']);
        
        // Delete DB record
        $stmt = $pdo->prepare("DELETE FROM songs WHERE id = ?");
        if ($stmt->execute([$id])) {
            $message = "Canción eliminada correctamente.";
        } else {
            $error = "Error al eliminar de la base de datos.";
        }
    } else {
        $error = "Canción no encontrada.";
    }
}

// Handle Video Deletion (File Cleanup)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_video') {
    $id = $_POST['id'];
    
    // Fetch paths
    $stmt = $pdo->prepare("SELECT video_path, cover_path FROM videos WHERE id = ?");
    $stmt->execute([$id]);
    $video = $stmt->fetch();
    
    if ($video) {
        if (file_exists($video['video_path'])) unlink($video['video_path']);
        if (file_exists($video['cover_path'])) unlink($video['cover_path']);
        
        $stmt = $pdo->prepare("DELETE FROM videos WHERE id = ?");
        if ($stmt->execute([$id])) {
            $message = "Video eliminado correctamente.";
        } else {
            $error = "Error al eliminar de la base de datos.";
        }
    } else {
         $error = "Video no encontrado.";
    }
}

// Handle Video Editing
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit_video') {
    $id = $_POST['video_id'];
    $title = trim($_POST['title']);
    
    // Fetch current data
    $stmt = $pdo->prepare("SELECT video_path, cover_path FROM videos WHERE id = ?");
    $stmt->execute([$id]);
    $currentVideo = $stmt->fetch();
    
    if ($currentVideo) {
        $videoPath = $currentVideo['video_path'];
        $coverPath = $currentVideo['cover_path'];
        $uploadDir = 'uploads/';
        
        // Handle New Cover
        if (isset($_FILES['video_cover']) && $_FILES['video_cover']['error'] === 0) {
            $coverName = uniqid() . '_thumb_' . basename($_FILES['video_cover']['name']);
            $newCoverPath = $uploadDir . $coverName;
            if (move_uploaded_file($_FILES['video_cover']['tmp_name'], $newCoverPath)) {
                if (file_exists($coverPath)) unlink($coverPath);
                $coverPath = $newCoverPath;
            }
        }
        
        // Handle New Video File
        if (isset($_FILES['video_file']) && $_FILES['video_file']['error'] === 0) {
            $videoName = uniqid() . '_vid_' . basename($_FILES['video_file']['name']);
            $newVideoPath = $uploadDir . $videoName;
            if (move_uploaded_file($_FILES['video_file']['tmp_name'], $newVideoPath)) {
                if (file_exists($videoPath)) unlink($videoPath);
                $videoPath = $newVideoPath;
            }
        }
        
        $stmt = $pdo->prepare("UPDATE videos SET title = ?, video_path = ?, cover_path = ? WHERE id = ?");
        if ($stmt->execute([$title, $videoPath, $coverPath, $id])) {
            $message = "Video actualizado correctamente.";
        } else {
             $error = "Error al actualizar video.";
        }
    } else {
         $error = "Video no encontrado.";
    }
}

// Handle Song Editing
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit_song') {
    $id = $_POST['song_id'];
    $title = trim($_POST['title']);
    $artist = trim($_POST['artist']);
    
    // Fetch current data
    $stmt = $pdo->prepare("SELECT cover_path, audio_path FROM songs WHERE id = ?");
    $stmt->execute([$id]);
    $currentSong = $stmt->fetch();
    
    if ($currentSong) {
        $coverPath = $currentSong['cover_path'];
        $audioPath = $currentSong['audio_path'];
        $uploadDir = 'uploads/';
        
        // Handle New Cover
        if (isset($_FILES['cover']) && $_FILES['cover']['error'] === 0) {
            $coverName = uniqid() . '_' . basename($_FILES['cover']['name']);
            $newCoverPath = $uploadDir . $coverName;
            if (move_uploaded_file($_FILES['cover']['tmp_name'], $newCoverPath)) {
                if (file_exists($coverPath)) unlink($coverPath); // Delete old
                $coverPath = $newCoverPath;
            }
        }
        
        // Handle New Audio
        if (isset($_FILES['audio']) && $_FILES['audio']['error'] === 0) {
            $audioName = uniqid() . '_' . basename($_FILES['audio']['name']);
            $newAudioPath = $uploadDir . $audioName;
            if (move_uploaded_file($_FILES['audio']['tmp_name'], $newAudioPath)) {
                if (file_exists($audioPath)) unlink($audioPath); // Delete old
                $audioPath = $newAudioPath;
            }
        }
        
        // Update DB
        $stmt = $pdo->prepare("UPDATE songs SET title = ?, artist = ?, cover_path = ?, audio_path = ? WHERE id = ?");
        if ($stmt->execute([$title, $artist, $coverPath, $audioPath, $id])) {
            $message = "Canción actualizada correctamente.";
        } else {
            $error = "Error al actualizar la base de datos.";
        }
    } else {
        $error = "Canción no encontrada.";
    }
}

// Fetch existing songs for management
$songs = $pdo->query("SELECT * FROM songs ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
// Fetch existing videos
$allVideos = $pdo->query("SELECT * FROM videos ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
// Calculate stats
$totalSongs = $pdo->query("SELECT COUNT(*) FROM songs")->fetchColumn();
$totalVideos = $pdo->query("SELECT COUNT(*) FROM videos")->fetchColumn();

// Fetch Statistics
$totalUsers = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$totalAdmins = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin'")->fetchColumn();

// Fetch Recent Activity (Combined)
$recentActivity = $pdo->query("
    (SELECT title, created_at, 'song' as type FROM songs) 
    UNION 
    (SELECT title, created_at, 'video' as type FROM videos) 
    ORDER BY created_at DESC LIMIT 20
")->fetchAll(PDO::FETCH_ASSOC);

// Determine Active Tab
// Determine Active Tab
$activeTab = 'upload';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $act = $_POST['action'];
    // Only switch to library if editing or deleting
    if ($act === 'edit_song' || $act === 'delete_song') {
        $activeTab = 'library';
    } elseif ($act === 'edit_video' || $act === 'delete_video') {
        $activeTab = 'video-library';
    }
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin | Trillos Records</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Outfit', sans-serif;
            background: radial-gradient(circle at top right, #1a1a1a 0%, #000000 100%);
        }

        .glass-panel {
            background: rgba(255, 255, 255, 0.03);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.05);
        }

        .glass-input {
            background: rgba(0, 0, 0, 0.4);
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: white;
            transition: all 0.3s ease;
        }

        .glass-input:focus {
            border-color: #fbbf24;
            box-shadow: 0 0 15px rgba(251, 191, 36, 0.15);
            outline: none;
        }

        .sidebar-link {
            transition: all 0.2s ease;
            position: relative;
        }

        .sidebar-link.active {
            color: #fbbf24;
            background: rgba(251, 191, 36, 0.1);
        }

        .sidebar-link.active::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 3px;
            background: #fbbf24;
            border-radius: 0 4px 4px 0;
        }

        /* Scrollbar custom */
        ::-webkit-scrollbar {
            width: 8px;
        }

        ::-webkit-scrollbar-track {
            background: #0f0f0f;
        }

        ::-webkit-scrollbar-thumb {
            background: #333;
            border-radius: 4px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: #444;
        }
    </style>
</head>

<body class="bg-black text-gray-200 min-h-screen flex md:overflow-hidden">

    <!-- Sidebar -->
    <aside class="w-64 glass-panel border-r border-white/5 flex flex-col z-20 hidden md:flex">
        <div class="p-8">
            <h1 class="text-2xl font-bold tracking-tight text-white flex items-center gap-3">
                <img src="./assets/Logo.png" alt="Trillos Records" class="h-12 w-auto object-contain">
                <span>Trillos<span class="text-yellow-500">Admin</span></span>
            </h1>
        </div>

        <nav class="flex-1 px-4 space-y-2">
            <p class="px-4 text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Principal</p>
            <button onclick="switchTab('upload')" id="nav-upload" class="sidebar-link w-full flex items-center gap-3 px-4 py-3 text-sm font-medium rounded-lg text-gray-400 hover:text-white hover:bg-white/5 <?php echo $activeTab === 'upload' ? 'active' : ''; ?>">
                <i data-lucide="upload-cloud" class="w-5 h-5"></i>
                Subir Contenido
            </button>
            <button onclick="switchTab('library')" id="nav-library" class="sidebar-link w-full flex items-center gap-3 px-4 py-3 text-sm font-medium rounded-lg text-gray-400 hover:text-white hover:bg-white/5 <?php echo $activeTab === 'library' ? 'active' : ''; ?>">
                <i data-lucide="library" class="w-5 h-5"></i>
                Biblioteca Musical
            </button>
            <button onclick="switchTab('stats')" id="nav-stats" class="sidebar-link w-full flex items-center gap-3 px-4 py-3 text-sm font-medium rounded-lg text-gray-400 hover:text-white hover:bg-white/5">
                <i data-lucide="bar-chart-2" class="w-5 h-5"></i>
                Estadísticas
            </button>
            <button onclick="switchTab('video-library')" id="nav-video-library" class="sidebar-link w-full flex items-center gap-3 px-4 py-3 text-sm font-medium rounded-lg text-gray-400 hover:text-white hover:bg-white/5">
                <i data-lucide="video" class="w-5 h-5"></i>
                Biblioteca de Videos
            </button>
            <a href="index.php" class="sidebar-link w-full flex items-center gap-3 px-4 py-3 text-sm font-medium rounded-lg text-red-500 hover:text-white hover:bg-red-600 transition-all duration-300 hover:scale-[1.02] hover:shadow-lg hover:shadow-red-600/20 group">
                <i data-lucide="log-out" class="w-5 h-5 group-hover:animate-pulse"></i>
                Volver al Sitio
            </a>
        </nav>
    </aside>

    <!-- Main Content -->
    <main class="flex-1 relative md:overflow-y-auto">
        <!-- Header Mobile -->
        <div class="md:hidden p-4 glass-panel flex justify-between items-center sticky top-0 z-30">
            <div class="flex items-center gap-2">
                <img src="./assets/Logo.png" alt="Logo" class="h-8 w-auto">
                <h1 class="font-bold text-lg text-white">Trillos<span class="text-yellow-500">Admin</span></h1>
            </div>
            <div class="flex items-center gap-3">
                 <a href="index.php" class="text-gray-400 hover:text-white" title="Volver al Sitio">
                    <i data-lucide="home" class="w-6 h-6"></i>
                </a>
                <button onclick="document.querySelector('aside').classList.toggle('hidden')" class="p-2 text-gray-300">
                    <i data-lucide="menu" class="w-6 h-6"></i>
                </button>
            </div>
        </div>

        <div class="p-8 max-w-7xl mx-auto">
            
            <!-- Quick Stats Removed -->

            <!-- Toast Messages -->
            <?php if ($message): ?>
                <div class="mb-8 p-4 rounded-xl bg-green-500/10 border border-green-500/20 text-green-400 flex items-center justify-between gap-3 animate-fade-in">
                    <div class="flex items-center gap-3">
                        <i data-lucide="check-circle-2" class="w-5 h-5"></i>
                        <?php echo htmlspecialchars($message); ?>
                    </div>
                    <a href="index.php" class="text-xs font-semibold bg-green-500/20 hover:bg-green-500/30 px-3 py-1.5 rounded-lg transition-colors">
                        Volver al Sitio
                    </a>
                </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="mb-8 p-4 rounded-xl bg-red-500/10 border border-red-500/20 text-red-400 flex items-center gap-3 animate-fade-in">
                    <i data-lucide="alert-circle" class="w-5 h-5"></i>
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <!-- View: Upload (Default) -->
            <div id="view-upload" class="section-view <?php echo $activeTab === 'upload' ? 'opacity-100' : 'hidden opacity-0'; ?> transition-opacity duration-300">
                <h2 class="text-2xl font-bold text-white mb-6">Subir Nuevo Contenido</h2>
                
                <div class="grid lg:grid-cols-3 gap-8">
                    <!-- Song Upload Card -->
                    <div class="lg:col-span-2 glass-panel rounded-2xl p-8">
                        <div class="flex items-center gap-3 mb-8">
                            <i data-lucide="music" class="text-yellow-500 w-6 h-6"></i>
                            <h3 class="text-xl font-semibold text-gray-100">Nueva Canción</h3>
                        </div>

                        <form action="admin.php" method="POST" enctype="multipart/form-data" class="space-y-6">
                            <input type="hidden" name="action" value="upload_song">
                            
                            <div class="grid md:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-xs font-medium text-gray-400 uppercase tracking-wide mb-2">Título</label>
                                    <input type="text" name="title" required class="w-full glass-input px-4 py-3 rounded-xl focus:ring-1 focus:ring-yellow-500/50" placeholder="Nombre de la pista">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-400 uppercase tracking-wide mb-2">Artista</label>
                                    <input type="text" name="artist" required class="w-full glass-input px-4 py-3 rounded-xl focus:ring-1 focus:ring-yellow-500/50" placeholder="Nombre del artista">
                                </div>
                            </div>

                            <div class="grid md:grid-cols-2 gap-6">
                                <!-- Cover Upload -->
                                <div>
                                    <label class="block text-xs font-medium text-gray-400 uppercase tracking-wide mb-2">Carátula</label>
                                    <label class="flex flex-col items-center justify-center w-full h-40 border-2 border-dashed border-gray-700 rounded-xl cursor-pointer hover:border-yellow-500/50 hover:bg-yellow-500/5 transition-all group relative overflow-hidden">
                                        <div class="flex flex-col items-center justify-center pt-5 pb-6 text-center z-10" id="cover-placeholder">
                                            <i data-lucide="image-plus" class="w-8 h-8 mb-3 text-gray-500 group-hover:text-yellow-500 transition-colors"></i>
                                            <p class="text-sm text-gray-500">Click para subir imagen</p>
                                        </div>
                                        <img id="cover-preview" class="absolute inset-0 w-full h-full object-cover opacity-0 transition-opacity">
                                        <input type="file" name="cover" accept="image/*" class="hidden" required onchange="previewImage(this)">
                                    </label>
                                </div>

                                <!-- Audio Upload -->
                                <div>
                                    <label class="block text-xs font-medium text-gray-400 uppercase tracking-wide mb-2">Archivo de Audio</label>
                                    <label class="flex flex-col items-center justify-center w-full h-40 border-2 border-dashed border-gray-700 rounded-xl cursor-pointer hover:border-blue-500/50 hover:bg-blue-500/5 transition-all group">
                                        <div class="flex flex-col items-center justify-center pt-5 pb-6 text-center">
                                            <i data-lucide="file-audio" class="w-8 h-8 mb-3 text-gray-500 group-hover:text-blue-500 transition-colors"></i>
                                            <p class="text-sm text-gray-500" id="audio-filename">Click para subir MP3</p>
                                        </div>
                                        <input type="file" name="audio" accept="audio/*" class="hidden" required onchange="updateFilename(this)">
                                    </label>
                                </div>
                            </div>

                            <div class="pt-4">
                                <button type="submit" class="w-full py-4 bg-yellow-500 hover:bg-yellow-400 text-black font-bold rounded-xl transition-all shadow-lg shadow-yellow-500/20 active:scale-[0.98]">
                                    Publicar Canción
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Video Add Card -->
                    <div class="glass-panel rounded-2xl p-8 h-full">
                        <div class="flex items-center gap-3 mb-8">
                            <i data-lucide="video" class="text-red-500 w-6 h-6"></i>
                            <h3 class="text-xl font-semibold text-gray-100">Subir Video</h3>
                        </div>
                        
                        <form action="admin.php" method="POST" enctype="multipart/form-data" class="space-y-6">
                            <input type="hidden" name="action" value="add_video">
                            
                            <div>
                                <label class="block text-xs font-medium text-gray-400 uppercase tracking-wide mb-2">Título del Video</label>
                                <input type="text" name="title" required class="w-full glass-input px-4 py-3 rounded-xl focus:ring-1 focus:ring-red-500/50">
                            </div>

                            <div>
                                <label class="block text-xs font-medium text-gray-400 uppercase tracking-wide mb-2">Archivo de Video (MP4)</label>
                                <input type="file" name="video_file" accept="video/mp4,video/webm" required class="w-full text-sm text-gray-400 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-red-600 file:text-white hover:file:bg-red-700">
                            </div>
                            
                            <div>
                                <label class="block text-xs font-medium text-gray-400 uppercase tracking-wide mb-2">Miniatura (Imagen)</label>
                                <input type="file" name="video_cover" accept="image/*" required class="w-full text-sm text-gray-400 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-gray-700 file:text-white hover:file:bg-gray-600">
                            </div>

                            <div class="pt-4">
                                <button type="submit" class="w-full py-4 bg-gradient-to-r from-red-600 to-red-700 hover:from-red-500 hover:to-red-600 text-white font-bold rounded-xl transition-all shadow-lg shadow-red-600/20 active:scale-[0.98]">
                                    Subir Video
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- View: Library (Hidden by default) -->
            <div id="view-library" class="section-view <?php echo $activeTab === 'library' ? 'opacity-100' : 'hidden opacity-0'; ?> transition-opacity duration-300">
                <div class="flex flex-col md:flex-row justify-between items-center mb-6 gap-4">
                    <h2 class="text-2xl font-bold text-white">Biblioteca Musical</h2>
                    <div class="relative w-full md:w-64">
                        <i data-lucide="search" class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-500"></i>
                        <input type="text" id="library-search" placeholder="Buscar canción..." class="w-full glass-input pl-10 pr-4 py-2 rounded-lg text-sm focus:ring-1 focus:ring-yellow-500/50">
                    </div>
                </div>

                <div class="glass-panel rounded-2xl overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left" id="library-table">
                            <thead>
                                <tr class="border-b border-gray-800 bg-black/20 text-xs uppercase tracking-wider text-gray-500">
                                    <th class="px-6 py-4">Track</th>
                                    <th class="px-6 py-4">Artista</th>
                                    <th class="px-6 py-4">Fecha</th>
                                    <th class="px-6 py-4 text-right">Acciones</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-800/50">
                                <?php foreach ($songs as $song): ?>
                                <tr class="group hover:bg-white/5 transition-colors">
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-4">
                                            <img src="<?php echo htmlspecialchars($song['cover_path']); ?>" class="w-12 h-12 rounded-lg object-cover shadow-lg">
                                            <span class="font-medium text-white"><?php echo htmlspecialchars($song['title']); ?></span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-gray-400"><?php echo htmlspecialchars($song['artist']); ?></td>
                                    <td class="px-6 py-4 text-gray-500 text-sm"><?php echo date('d M Y', strtotime($song['created_at'])); ?></td>
                                    <td class="px-6 py-4 text-right">
                                        <button onclick='openEditModal(<?php echo json_encode($song); ?>)' class="p-2 text-gray-500 hover:text-yellow-500 hover:bg-yellow-500/10 rounded-lg transition-all mr-2" title="Editar">
                                            <i data-lucide="pencil" class="w-5 h-5"></i>
                                        </button>
                                        <button onclick="openDeleteModal(<?php echo $song['id']; ?>, 'song')" class="p-2 text-gray-500 hover:text-red-500 hover:bg-red-500/10 rounded-lg transition-all" title="Eliminar">
                                            <i data-lucide="trash-2" class="w-5 h-5"></i>
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php if (empty($songs)): ?>
                        <div class="p-10 text-center text-gray-500">
                            <i data-lucide="music" class="w-12 h-12 mx-auto mb-3 opacity-20"></i>
                            <p>Tu biblioteca está vacía.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- View: Video Library -->
            <div id="view-video-library" class="section-view <?php echo $activeTab === 'video-library' ? 'opacity-100' : 'hidden opacity-0'; ?> transition-opacity duration-300">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-2xl font-bold text-white">Biblioteca de Videos</h2>
                </div>

                <div class="glass-panel rounded-2xl overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left">
                            <thead>
                                <tr class="border-b border-gray-800 bg-black/20 text-xs uppercase tracking-wider text-gray-500">
                                    <th class="px-6 py-4">Miniatura</th>
                                    <th class="px-6 py-4">Título</th>
                                    <th class="px-6 py-4 text-right">Acciones</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-800/50">
                                <?php foreach ($allVideos as $vid): ?>
                                <tr class="group hover:bg-white/5 transition-colors">
                                    <td class="px-6 py-4">
                                        <img src="<?php echo htmlspecialchars($vid['cover_path']); ?>" class="w-24 h-16 object-cover rounded-lg shadow-md bg-gray-800">
                                    </td>
                                    <td class="px-6 py-4 font-medium text-white"><?php echo htmlspecialchars($vid['title']); ?></td>
                                    <td class="px-6 py-4 text-right">
                                        <button onclick='openEditVideoModal(<?php echo json_encode($vid); ?>)' class="p-2 text-gray-500 hover:text-yellow-500 hover:bg-yellow-500/10 rounded-lg transition-all mr-2" title="Editar">
                                            <i data-lucide="pencil" class="w-5 h-5"></i>
                                        </button>
                                        <button onclick="openDeleteModal(<?php echo $vid['id']; ?>, 'video')" class="p-2 text-gray-500 hover:text-red-500 hover:bg-red-500/10 rounded-lg transition-all" title="Eliminar">
                                            <i data-lucide="trash-2" class="w-5 h-5"></i>
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php if (empty($allVideos)): ?>
                        <div class="p-10 text-center text-gray-500">
                            <i data-lucide="video" class="w-12 h-12 mx-auto mb-3 opacity-20"></i>
                            <p>No hay videos añadidos.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- View: Statistics -->
            <div id="view-stats" class="section-view hidden opacity-0 transition-opacity duration-300">
                <h2 class="text-2xl font-bold text-white mb-6">Panel de Estadísticas</h2>
                
                <!-- Detailed Stats Grid -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-10">
                    <div class="glass-panel p-6 rounded-2xl">
                        <div class="flex items-center justify-between mb-4">
                            <div class="p-3 rounded-xl bg-yellow-500/10 text-yellow-500">
                                <i data-lucide="music" class="w-6 h-6"></i>
                            </div>
                            <span class="text-xs font-medium text-yellow-500 bg-yellow-500/10 px-2 py-1 rounded-lg">+1 esta semana</span>
                        </div>
                        <p class="text-3xl font-bold text-white mb-1"><?php echo $totalSongs; ?></p>
                        <p class="text-sm text-gray-400">Canciones</p>
                    </div>

                    <div class="glass-panel p-6 rounded-2xl">
                        <div class="flex items-center justify-between mb-4">
                            <div class="p-3 rounded-xl bg-blue-500/10 text-blue-500">
                                <i data-lucide="users" class="w-6 h-6"></i>
                            </div>
                        </div>
                        <p class="text-3xl font-bold text-white mb-1"><?php echo $totalUsers; ?></p>
                         <p class="text-sm text-gray-400">Usuarios Registrados</p>
                    </div>

                    <div class="glass-panel p-6 rounded-2xl">
                        <div class="flex items-center justify-between mb-4">
                            <div class="p-3 rounded-xl bg-red-500/10 text-red-500">
                                <i data-lucide="video" class="w-6 h-6"></i>
                            </div>
                        </div>
                        <p class="text-3xl font-bold text-white mb-1"><?php echo $totalVideos; ?></p>
                        <p class="text-sm text-gray-400">Videos</p>
                    </div>
                </div>

                <!-- Recent Activity Feed -->
                <div class="glass-panel rounded-2xl p-8 h-[500px] flex flex-col">
                    <h3 class="text-xl font-bold text-white mb-6">Actividad Reciente</h3>
                    <div class="space-y-6 overflow-y-auto pr-2 custom-scrollbar flex-1">
                        <?php foreach ($recentActivity as $activity): ?>
                        <div class="flex items-start gap-4 pb-6 border-b border-gray-800 last:border-0 last:pb-0">
                            <div class="mt-1">
                                <?php if($activity['type'] == 'song'): ?>
                                    <div class="p-2 rounded-full bg-yellow-500/10 text-yellow-500">
                                        <i data-lucide="music-2" class="w-4 h-4"></i>
                                    </div>
                                <?php else: ?>
                                    <div class="p-2 rounded-full bg-red-500/10 text-red-500">
                                        <i data-lucide="video" class="w-4 h-4"></i>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div>
                                <p class="text-white font-medium">
                                    Nuevo <?php echo $activity['type'] == 'song' ? 'tema musical' : 'video'; ?> añadido
                                </p>
                                <p class="text-gray-400 text-sm mt-0.5">
                                    "<?php echo htmlspecialchars($activity['title']); ?>"
                                </p>
                                <p class="text-xs text-gray-600 mt-2">
                                    <?php echo date('d M Y, h:i A', strtotime($activity['created_at'])); ?>
                                </p>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

        </div>
    </main>

        </div>
    </div>

        </div>
    </div>

    <!-- Circular Progress Banner -->
    <div id="upload-progress-banner" class="fixed bottom-0 left-0 w-full z-50 bg-[#111] border-t border-yellow-500/30 p-4 transform translate-y-full transition-transform duration-300 shadow-[0_-5px_30px_rgba(0,0,0,0.8)]">
        <div id="banner-inner" class="max-w-4xl mx-auto flex items-center justify-between transition-all duration-300">
            
            <!-- Left: Icon & Text -->
            <div class="flex items-center gap-6">
                <!-- Circular Progress Ring -->
                <div class="relative w-14 h-14 flex items-center justify-center">
                    <svg class="w-full h-full transform -rotate-90">
                        <circle cx="28" cy="28" r="24" stroke="currentColor" stroke-width="4" fill="transparent" class="text-gray-800" />
                        <circle id="progress-ring" cx="28" cy="28" r="24" stroke="currentColor" stroke-width="4" fill="transparent" class="text-yellow-500 transition-all duration-300 ease-out" stroke-dasharray="150.72" stroke-dashoffset="150.72" />
                    </svg>
                    <!-- Check Icon (Hidden by default) -->
                    <div id="success-checkmark" class="absolute inset-0 flex items-center justify-center opacity-0 scale-50 transition-all duration-300 text-green-500">
                        <i data-lucide="check" class="w-8 h-8"></i>
                    </div>
                     <!-- Percentage Text -->
                    <span id="progress-text" class="absolute text-[10px] font-bold text-white">0%</span>
                </div>

                <div>
                    <h4 class="text-white font-bold text-lg" id="progress-title">Subiendo archivos...</h4>
                    <p class="text-sm text-gray-400" id="progress-detail">Por favor espera</p>
                </div>
            </div>

            <!-- Right: Action Button -->
             <a href="index.php" id="upload-back-btn" class="hidden opacity-0 transform translate-x-4 transition-all duration-500 bg-white/10 hover:bg-white/20 text-white px-6 py-3 rounded-full font-medium flex items-center gap-2 group">
                <span>Volver al Sitio</span>
                <i data-lucide="arrow-right" class="w-4 h-4 group-hover:translate-x-1 transition-transform"></i>
            </a>

        </div>
    </div>

    <!-- Edit Video Modal -->
    <div id="edit-video-modal" class="fixed inset-0 z-50 hidden">
        <div class="absolute inset-0 bg-black/80 backdrop-blur-sm transition-opacity opacity-0" id="edit-video-backdrop"></div>
        <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-full max-w-lg p-4 scale-95 opacity-0 transition-all duration-300" id="edit-video-content">
            <div class="glass-panel bg-[#111] rounded-2xl p-6 border border-white/10 shadow-2xl">
                <div class="flex items-center gap-3 mb-6 border-b border-white/10 pb-4">
                    <i data-lucide="video" class="w-6 h-6 text-yellow-500"></i>
                    <h2 class="text-xl font-bold text-white">Editar Video</h2>
                </div>
                
                <form action="admin.php" method="POST" enctype="multipart/form-data" class="space-y-4">
                    <input type="hidden" name="action" value="edit_video">
                    <input type="hidden" name="video_id" id="edit-video-id">
                    
                    <div>
                        <label class="block text-xs font-medium text-gray-400 mb-1">Título</label>
                        <input type="text" name="title" id="edit-video-title" required class="w-full glass-input px-3 py-2 rounded-lg">
                    </div>
                    
                    <div>
                        <label class="block text-xs font-medium text-gray-400 mb-1">Cambiar Video (Opcional)</label>
                        <input type="file" name="video_file" accept="video/mp4" class="w-full text-sm text-gray-400 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-red-600 file:text-white hover:file:bg-red-700">
                    </div>

                    <div>
                         <label class="block text-xs font-medium text-gray-400 mb-1">Cambiar Miniatura (Opcional)</label>
                         <input type="file" name="video_cover" accept="image/*" class="w-full text-sm text-gray-400 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-gray-700 file:text-white hover:file:bg-gray-600">
                    </div>
                    
                    <div class="flex gap-3 pt-4">
                        <button type="button" onclick="closeEditVideoModal()" class="flex-1 py-3 rounded-xl bg-white/5 hover:bg-white/10 text-gray-300 font-medium transition-colors">Cancelar</button>
                        <button type="submit" class="flex-1 py-3 rounded-xl bg-yellow-500 hover:bg-yellow-400 text-black font-bold transition-colors shadow-lg shadow-yellow-500/20">Guardar Cambios</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Modal -->
    <div id="edit-modal" class="fixed inset-0 z-50 hidden">
        <div class="absolute inset-0 bg-black/80 backdrop-blur-sm transition-opacity opacity-0" id="edit-backdrop"></div>
        <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-full max-w-lg p-4 scale-95 opacity-0 transition-all duration-300" id="edit-content">
            <div class="glass-panel bg-[#111] rounded-2xl p-6 border border-white/10 shadow-2xl">
                <div class="flex items-center gap-3 mb-6 border-b border-white/10 pb-4">
                    <i data-lucide="pencil" class="w-6 h-6 text-yellow-500"></i>
                    <h2 class="text-xl font-bold text-white">Editar Canción</h2>
                </div>
                
                <form action="admin.php" method="POST" enctype="multipart/form-data" class="space-y-4">
                    <input type="hidden" name="action" value="edit_song">
                    <input type="hidden" name="song_id" id="edit-song-id">
                    
                    <div class="grid md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-medium text-gray-400 mb-1">Título</label>
                            <input type="text" name="title" id="edit-title" required class="w-full glass-input px-3 py-2 rounded-lg">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-400 mb-1">Artista</label>
                            <input type="text" name="artist" id="edit-artist" required class="w-full glass-input px-3 py-2 rounded-lg">
                        </div>
                    </div>
                    
                    <div>
                        <label class="block text-xs font-medium text-gray-400 mb-1">Cambiar Carátula (Opcional)</label>
                        <input type="file" name="cover" accept="image/*" class="w-full text-sm text-gray-400 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-yellow-600 file:text-black hover:file:bg-yellow-500">
                    </div>
                    
                    <div>
                        <label class="block text-xs font-medium text-gray-400 mb-1">Cambiar Audio (Opcional)</label>
                        <input type="file" name="audio" accept="audio/*" class="w-full text-sm text-gray-400 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-yellow-600 file:text-black hover:file:bg-yellow-500">
                    </div>
                    
                    <div class="flex gap-3 pt-4">
                        <button type="button" onclick="closeEditModal()" class="flex-1 py-3 rounded-xl bg-white/5 hover:bg-white/10 text-gray-300 font-medium transition-colors">Cancelar</button>
                        <button type="submit" class="flex-1 py-3 rounded-xl bg-yellow-500 hover:bg-yellow-400 text-black font-bold transition-colors shadow-lg shadow-yellow-500/20">Guardar Cambios</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div id="delete-modal" class="fixed inset-0 z-50 hidden">
        <!-- Backdrop -->
        <div class="absolute inset-0 bg-black/80 backdrop-blur-sm transition-opacity opacity-0" id="delete-backdrop"></div>
        
        <!-- Modal Content -->
        <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-full max-w-sm p-4 scale-95 opacity-0 transition-all duration-300" id="delete-content">
            <div class="glass-panel bg-[#111] rounded-2xl p-6 border border-white/10 shadow-2xl">
                <div class="flex flex-col items-center text-center">
                    <div class="w-12 h-12 rounded-full bg-red-500/10 flex items-center justify-center text-red-500 mb-4">
                        <i data-lucide="alert-triangle" class="w-6 h-6"></i>
                    </div>
                    <h3 class="text-xl font-bold text-white mb-2">¿Eliminar canción?</h3>
                    <p class="text-gray-400 text-sm mb-6">Esta acción no se puede deshacer. Los archivos se borrarán del servidor.</p>
                    
                    <div class="flex gap-3 w-full">
                        <button onclick="closeDeleteModal()" class="flex-1 py-2.5 rounded-xl bg-white/5 hover:bg-white/10 text-gray-300 font-medium transition-colors">
                            Cancelar
                        </button>
                        <form id="delete-form" action="admin.php" method="POST" class="flex-1">
                            <input type="hidden" name="action" id="delete-action" value="delete_song">
                            <input type="hidden" name="id" id="delete-id">
                            <button type="submit" class="w-full py-2.5 rounded-xl bg-red-600 hover:bg-red-500 text-white font-bold transition-colors shadow-lg shadow-red-600/20">
                                Eliminar
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        lucide.createIcons();
        
         // ------------------------------
        // UPLOAD PROGRESS LOGIC (CIRCULAR)
        // ------------------------------
        document.addEventListener('DOMContentLoaded', () => {
             const uploadForms = document.querySelectorAll('#view-upload form');
             
             uploadForms.forEach(form => {
                 form.addEventListener('submit', function(e) {
                     e.preventDefault();
                     handleUpload(this);
                 });
             });
        });

        function handleUpload(form) {
            const banner = document.getElementById('upload-progress-banner');
            const ring = document.getElementById('progress-ring');
            const percentText = document.getElementById('progress-text');
            const titleEl = document.getElementById('progress-title');
            const detailEl = document.getElementById('progress-detail');
            const backBtn = document.getElementById('upload-back-btn');
            const checkmark = document.getElementById('success-checkmark');
            const bannerInner = document.getElementById('banner-inner');

            // Circle properties (r=24 -> circumference ~ 150.72)
            const circumference = 2 * Math.PI * 24;
            ring.style.strokeDasharray = `${circumference} ${circumference}`;
            ring.style.strokeDashoffset = circumference;

            // Reset UI
            banner.classList.remove('translate-y-full', 'bg-green-900/40', 'border-green-500/50');
            backBtn.classList.add('hidden', 'opacity-0');
            checkmark.classList.add('opacity-0', 'scale-50');
            percentText.classList.remove('opacity-0'); // Show percent text
            ring.classList.remove('text-green-500');
            ring.classList.add('text-yellow-500');
            
            titleEl.textContent = "Subiendo archivos...";
            detailEl.textContent = "Por favor espera";
            
            const formData = new FormData(form);
            const xhr = new XMLHttpRequest();

            xhr.open('POST', 'admin.php', true);
            xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');

            // Progress
            xhr.upload.onprogress = function(e) {
                if (e.lengthComputable) {
                    const percent = e.loaded / e.total;
                    const offset = circumference - (percent * circumference);
                    
                    ring.style.strokeDashoffset = offset;
                    percentText.textContent = Math.round(percent * 100) + '%';
                    
                    if(percent < 1) {
                         titleEl.textContent = "Subiendo...";
                    } else {
                         titleEl.textContent = "Procesando...";
                    }
                }
            };

            // Complete
            xhr.onload = function() {
                if (xhr.status === 200) {
                    try {
                        const res = JSON.parse(xhr.responseText);
                        if(res.success) {
                            // 100% Progress
                            ring.style.strokeDashoffset = 0;
                            percentText.textContent = ''; // Hide text
                            
                            // Success transformation
                            titleEl.textContent = "¡Subida Exitosa!";
                            detailEl.textContent = "Proceso completado";
                            
                            banner.classList.add('bg-green-900/40', 'border-green-500/50');
                            ring.classList.remove('text-yellow-500');
                            ring.classList.add('text-green-500');
                            
                            // Show Checkmark
                            percentText.classList.add('opacity-0');
                            checkmark.classList.remove('opacity-0', 'scale-50');
                            
                            // Show Button
                            backBtn.classList.remove('hidden');
                            setTimeout(() =>backBtn.classList.remove('opacity-0', 'translate-x-4'), 50);

                            // Auto Hide
                            setTimeout(() => {
                                banner.classList.add('translate-y-full');
                            }, 3000);
                            
                        } else {
                            alert('Error: ' + res.message);
                            banner.classList.add('translate-y-full');
                        }
                    } catch (e) {
                         console.error(e);
                         banner.classList.add('translate-y-full');
                         // Fallback reload if valid response is messed up but maybe uploaded
                         if(xhr.responseText.includes('success')) location.reload();
                    }
                } else {
                     alert('Error de conexión.');
                     banner.classList.add('translate-y-full');
                }
            };
             
            xhr.onerror = function() {
                 alert('Error de red.');
                 banner.classList.add('translate-y-full');
            };

            xhr.send(formData);
        }
        
         // ------------------------------
        // UPLOAD PROGRESS LOGIC (CIRCULAR)
        // ------------------------------
        document.addEventListener('DOMContentLoaded', () => {
             const uploadForms = document.querySelectorAll('#view-upload form');
             
             uploadForms.forEach(form => {
                 form.addEventListener('submit', function(e) {
                     e.preventDefault();
                     handleUpload(this);
                 });
             });
        });

        function handleUpload(form) {
            const banner = document.getElementById('upload-progress-banner');
            const ring = document.getElementById('progress-ring');
            const percentText = document.getElementById('progress-text');
            const titleEl = document.getElementById('progress-title');
            const detailEl = document.getElementById('progress-detail');
            const backBtn = document.getElementById('upload-back-btn');
            const checkmark = document.getElementById('success-checkmark');
            const bannerInner = document.getElementById('banner-inner');

            // Circle properties (r=24 -> circumference ~ 150.72)
            const circumference = 2 * Math.PI * 24;
            ring.style.strokeDasharray = `${circumference} ${circumference}`;
            ring.style.strokeDashoffset = circumference;

            // Reset UI
            banner.classList.remove('translate-y-full', 'bg-green-900/40', 'border-green-500/50');
            backBtn.classList.add('hidden', 'opacity-0');
            checkmark.classList.add('opacity-0', 'scale-50');
            percentText.classList.remove('opacity-0'); // Show percent text
            ring.classList.remove('text-green-500');
            ring.classList.add('text-yellow-500');
            
            titleEl.textContent = "Subiendo archivos...";
            detailEl.textContent = "Por favor espera";
            
            const formData = new FormData(form);
            const xhr = new XMLHttpRequest();

            xhr.open('POST', 'admin.php', true);
            xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');

            // Progress
            xhr.upload.onprogress = function(e) {
                if (e.lengthComputable) {
                    const percent = e.loaded / e.total;
                    const offset = circumference - (percent * circumference);
                    
                    ring.style.strokeDashoffset = offset;
                    percentText.textContent = Math.round(percent * 100) + '%';
                    
                    if(percent < 1) {
                         titleEl.textContent = "Subiendo...";
                    } else {
                         titleEl.textContent = "Procesando...";
                    }
                }
            };

            // Complete
            xhr.onload = function() {
                if (xhr.status === 200) {
                    try {
                        const res = JSON.parse(xhr.responseText);
                        if(res.success) {
                            // 100% Progress
                            ring.style.strokeDashoffset = 0;
                            percentText.textContent = ''; // Hide text
                            
                            // Success transformation
                            titleEl.textContent = "¡Subida Exitosa!";
                            detailEl.textContent = "Proceso completado";
                            
                            banner.classList.add('bg-green-900/40', 'border-green-500/50');
                            ring.classList.remove('text-yellow-500');
                            ring.classList.add('text-green-500');
                            
                            // Show Checkmark
                            percentText.classList.add('opacity-0');
                            checkmark.classList.remove('opacity-0', 'scale-50');
                            
                            // Show Button
                            backBtn.classList.remove('hidden');
                            setTimeout(() =>backBtn.classList.remove('opacity-0', 'translate-x-4'), 50);

                            // Auto Hide
                            setTimeout(() => {
                                banner.classList.add('translate-y-full');
                            }, 3000);
                            
                        } else {
                            alert('Error: ' + res.message);
                            banner.classList.add('translate-y-full');
                        }
                    } catch (e) {
                         console.error(e);
                         alert('Error del servidor.');
                         banner.classList.add('translate-y-full');
                    }
                } else {
                     alert('Error de conexión.');
                     banner.classList.add('translate-y-full');
                }
            };
             
            xhr.onerror = function() {
                 alert('Error de red.');
                 banner.classList.add('translate-y-full');
            };

            xhr.send(formData);
        }
        
        // Initial Tab State
        document.addEventListener('DOMContentLoaded', () => {
            const activeTab = '<?php echo $activeTab; ?>';
            switchTab(activeTab);
        });

        // Search Functionality
        document.getElementById('library-search').addEventListener('input', function(e) {
            const searchTerm = e.target.value.toLowerCase();
            const rows = document.querySelectorAll('#library-table tbody tr');
            
            rows.forEach(row => {
                if(row.classList.contains('no-results')) return;
                
                const title = row.querySelector('.font-medium').textContent.toLowerCase();
                const artist = row.querySelectorAll('td')[1].textContent.toLowerCase();
                
                if (title.includes(searchTerm) || artist.includes(searchTerm)) {
                    row.style.display = 'table-row';
                } else {
                    row.style.display = 'none';
                }
            });
        });

        // Modal Functionality
        function openDeleteModal(id, type = 'song') {
            const modal = document.getElementById('delete-modal');
            const backdrop = document.getElementById('delete-backdrop');
            const content = document.getElementById('delete-content');
            
            document.getElementById('delete-id').value = id;
            
            // Set action based on type
            const actionInput = document.getElementById('delete-action');
            if (type === 'video') {
                actionInput.value = 'delete_video';
            } else {
                actionInput.value = 'delete_song';
            }
            
            modal.classList.remove('hidden');
            // Animate in
            setTimeout(() => {
                backdrop.classList.remove('opacity-0');
                content.classList.remove('scale-95', 'opacity-0');
                content.classList.add('scale-100', 'opacity-100');
            }, 10);
        }

        function closeDeleteModal() {
            const modal = document.getElementById('delete-modal');
            const backdrop = document.getElementById('delete-backdrop');
            const content = document.getElementById('delete-content');
            
            // Animate out
            backdrop.classList.add('opacity-0');
            content.classList.remove('scale-100', 'opacity-100');
            content.classList.add('scale-95', 'opacity-0');
            
            setTimeout(() => {
                modal.classList.add('hidden');
            }, 300);
        }

        // Close modal on backdrop click
        document.getElementById('delete-backdrop').addEventListener('click', closeDeleteModal);
        document.getElementById('edit-backdrop').addEventListener('click', closeEditModal);
        document.getElementById('edit-video-backdrop').addEventListener('click', closeEditVideoModal);

        // Edit Video Modal Functions
        function openEditVideoModal(video) {
            const modal = document.getElementById('edit-video-modal');
            const backdrop = document.getElementById('edit-video-backdrop');
            const content = document.getElementById('edit-video-content');
            
            document.getElementById('edit-video-id').value = video.id;
            document.getElementById('edit-video-title').value = video.title;
            
            modal.classList.remove('hidden');
            setTimeout(() => {
                backdrop.classList.remove('opacity-0');
                content.classList.remove('scale-95', 'opacity-0');
                content.classList.add('scale-100', 'opacity-100');
            }, 10);
        }

        function closeEditVideoModal() {
            const modal = document.getElementById('edit-video-modal');
            const backdrop = document.getElementById('edit-video-backdrop');
            const content = document.getElementById('edit-video-content');
            
            backdrop.classList.add('opacity-0');
            content.classList.remove('scale-100', 'opacity-100');
            content.classList.add('scale-95', 'opacity-0');
            
            setTimeout(() => {
                modal.classList.add('hidden');
            }, 300);
        }

        // Edit Modal Functions
        function openEditModal(song) {
            const modal = document.getElementById('edit-modal');
            const backdrop = document.getElementById('edit-backdrop');
            const content = document.getElementById('edit-content');
            
            document.getElementById('edit-song-id').value = song.id;
            document.getElementById('edit-title').value = song.title;
            document.getElementById('edit-artist').value = song.artist;
            
            modal.classList.remove('hidden');
            setTimeout(() => {
                backdrop.classList.remove('opacity-0');
                content.classList.remove('scale-95', 'opacity-0');
                content.classList.add('scale-100', 'opacity-100');
            }, 10);
        }

        function closeEditModal() {
            const modal = document.getElementById('edit-modal');
            const backdrop = document.getElementById('edit-backdrop');
            const content = document.getElementById('edit-content');
            
            backdrop.classList.add('opacity-0');
            content.classList.remove('scale-100', 'opacity-100');
            content.classList.add('scale-95', 'opacity-0');
            
            setTimeout(() => {
                modal.classList.add('hidden');
            }, 300);
        }

        // Simple Tab Switching Logic
        function switchTab(tabName) {
            // Update sidebar active state
            document.querySelectorAll('.sidebar-link').forEach(link => link.classList.remove('active'));
            document.getElementById('nav-' + tabName).classList.add('active');

            // Hide all views
            document.querySelectorAll('.section-view').forEach(el => {
                el.classList.add('hidden');
                el.classList.remove('opacity-100');
                el.classList.add('opacity-0');
            });

            // Show selected view
            const view = document.getElementById('view-' + tabName);
            view.classList.remove('hidden');
            // Small timeout for fade transition
            setTimeout(() => {
                view.classList.remove('opacity-0');
                view.classList.add('opacity-100');
            }, 10);
        }

        // Image Preview Logic
        function previewImage(input) {
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const preview = document.getElementById('cover-preview');
                    preview.src = e.target.result;
                    preview.classList.remove('opacity-0');
                    document.getElementById('cover-placeholder').classList.add('opacity-0'); // Hide text
                }
                reader.readAsDataURL(input.files[0]);
            }
        }

        // MP3 Filename Update
        function updateFilename(input) {
            if (input.files.length > 0) {
                document.getElementById('audio-filename').textContent = input.files[0].name;
                document.getElementById('audio-filename').classList.add('text-blue-400', 'font-medium');
            }
        }
    </script>
</body>
</html>
