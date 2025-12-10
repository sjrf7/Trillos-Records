<?php
session_start();
require 'db.php';

// Fetch songs
try {
    $stmt = $pdo->query("SELECT * FROM songs ORDER BY created_at DESC");
    $songs = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $songs = [];
}

// Fetch videos
try {
    $stmt = $pdo->query("SELECT * FROM videos ORDER BY created_at DESC");
    $videos = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $videos = [];
}


// Fetch user info and likes if logged in
$userProfilePic = null;
$likedSongs = [];
$likedVideos = [];

if (isset($_SESSION['user_id'])) {
    try {
        // User Info
        $stmt = $pdo->prepare("SELECT profile_pic FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $tempPic = $stmt->fetchColumn();
        
        // Validate image: URL or existing local file
        if ($tempPic && (filter_var($tempPic, FILTER_VALIDATE_URL) || file_exists($tempPic))) {
            $userProfilePic = $tempPic;
        } else {
            $userProfilePic = null;
        }

        // Liked Songs
        $stmt = $pdo->prepare("SELECT item_id FROM likes WHERE user_id = ? AND type = 'song'");
        $stmt->execute([$_SESSION['user_id']]);
        $likedSongs = $stmt->fetchAll(PDO::FETCH_COLUMN);

        // Liked Videos
        $stmt = $pdo->prepare("SELECT item_id FROM likes WHERE user_id = ? AND type = 'video'");
        $stmt->execute([$_SESSION['user_id']]);
        $likedVideos = $stmt->fetchAll(PDO::FETCH_COLUMN);
    } catch (Exception $e) {
        // Ignore error
    }
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trillos Visual Records | Música y Videos</title>
    <!-- Carga de Tailwind CSS para el estilizado moderno y responsivo -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Fuente Inter para legibilidad -->
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@100..900&family=Playfair+Display:ital,wght@0,400..900;1,400..900&display=swap"
        rel="stylesheet">
    <!-- Iconos de Lucide (para compartir y control de audio) -->
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        /* Configuración personalizada de Tailwind y fuentes */
        :root {
            --color-primary: #FFD700;
            /* Dorado */
            --color-background: #000000;
            /* Negro */
            --color-text: #E5E5E5;
            /* Gris claro */
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--color-background);
            color: var(--color-text);
            /* Estilo del fondo degradado negro a dorado (sutil y moderno) */
            background: radial-gradient(circle at top center, rgba(25, 25, 25, 1) 0%, #000000 100%);
            min-height: 100vh;
            padding-bottom: 6rem;
            /* Espacio para el reproductor fijo */
        }

        /* Estilo para el Hero (video de cabecera) */
        .hero-video-container {
            position: relative;
            height: 100vh;
            /* Altura completa para evitar espacios grises */
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 0;
        }

        .hero-video {
            position: absolute;
            top: 50%;
            left: 50%;
            min-width: 100%;
            min-height: 100%;
            width: auto;
            height: auto;
            z-index: 0;
            transform: translate(-50%, -50%);
            object-fit: cover;
        }

        /* Capa oscura con degradado para que el texto dorado resalte */
        .hero-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(180deg, rgba(0, 0, 0, 0.6) 0%, rgba(0, 0, 0, 0.8) 70%, #000000 100%);
            z-index: 1;
        }

        .hero-content {
            z-index: 2;
        }

        /* Estilo para el título de la marca, usando una fuente elegante (Playfair Display) y color dorado */
        .brand-title {
            font-family: 'Playfair Display', serif;
            color: var(--color-primary);
            text-shadow: 0 0 10px rgba(255, 215, 0, 0.4);
        }

        /* Estilo para el botón de compartir con efecto hover */
        .share-button {
            transition: all 0.3s ease;
        }

        .share-button:hover {
            transform: translateY(-2px) scale(1.05);
            box-shadow: 0 4px 15px rgba(255, 215, 0, 0.3);
            background-color: rgba(255, 215, 0, 0.1);
        }

        /* Estilo para el reproductor fijo inferior (Global Audio Player) */
        /* Estilo para el reproductor fijo inferior (Global Audio Player) */
        #audio-player-bar {
            position: fixed;
            bottom: 1.5rem;
            left: 50%;
            /* transform: translateX(-50%); REMOVED to allow JS/Tailwind transform control */
            width: 95%;
            max-width: 1200px;
            z-index: 900;
            backdrop-filter: blur(20px);
            background-color: rgba(10, 10, 10, 0.85);
            border-radius: 20px;
            border: 1px solid rgba(255, 215, 0, 0.2);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5), 0 0 20px rgba(255, 215, 0, 0.1);
        }

        /* Waveform Seek Bar Styles */
        .waveform-container {
            position: relative;
            width: 100%;
            height: 30px;
            display: flex;
            align-items: center;
            cursor: pointer;
        }

        .waveform-bg {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: repeating-linear-gradient(90deg,
                    rgba(255, 255, 255, 0.1) 0px,
                    rgba(255, 255, 255, 0.1) 2px,
                    transparent 2px,
                    transparent 4px);
            border-radius: 4px;
            pointer-events: none;
        }

        .waveform-active {
            position: absolute;
            top: 0;
            left: 0;
            height: 100%;
            width: 0%;
            background: repeating-linear-gradient(90deg,
                    #FFD700 0px,
                    #FFD700 2px,
                    transparent 2px,
                    transparent 4px);
            border-radius: 4px;
            pointer-events: none;
            transition: width 0.1s linear;
        }

        /* Invisible range input on top */
        .waveform-input {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            opacity: 0;
            cursor: pointer;
            z-index: 10;
            margin: 0;
        }

        /* Animación para el icono de play */
        @keyframes pulse-gold {
            0% {
                box-shadow: 0 0 0 0 rgba(255, 215, 0, 0.4);
            }

            70% {
                box-shadow: 0 0 0 10px rgba(255, 215, 0, 0);
            }

            100% {
                box-shadow: 0 0 0 0 rgba(255, 215, 0, 0);
            }
        }

        .pulsing-play {
            animation: pulse-gold 1.5s infinite;
        }

        /* Animación para el borde neón */
        @keyframes border-flow {
            0% {
                background-position: 0% 50%;
            }

            100% {
                background-position: 200% 50%;
            }
        }

        /* iOS Style Top Dock */
        .ios-dock {
            position: fixed;
            top: 1.5rem;
            left: 50%;
            transform: translateX(-50%);
            z-index: 2005;
            display: flex;
            align-items: center;
            padding: 0.6rem 3rem;

            /* Fondo doble: interior oscuro, borde con gradiente animado */
            background: linear-gradient(rgba(20, 20, 20, 0.85), rgba(20, 20, 20, 0.85)) padding-box,
                linear-gradient(45deg, transparent 35%, rgba(255, 255, 0, 0.9) 50%, transparent 65%) border-box;
            background-size: 200% 200%;
            animation: border-flow 3s linear infinite;

            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 2px solid transparent;
            /* Transparente para mostrar el gradiente de fondo */
            border-radius: 9999px;

            /* Sombra con resplandor dorado sutil */
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.5), 0 0 15px rgba(255, 255, 0, 0.3);

            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            width: auto;
            max-width: 90vw;
        }

        .ios-dock:hover {
            /* Mantiene la animación pero intensifica el brillo */
            box-shadow: 0 15px 50px rgba(0, 0, 0, 0.6), 0 0 25px rgba(255, 255, 0, 0.5);
            transform: translateX(-50%) translateY(-2px);
            background-size: 150% 150%;
            /* Acelera visualmente el efecto al hover */
        }

        .ios-dock-logo {
            width: 3rem;
            height: 3rem;
            border-radius: 50%;
            overflow: hidden;
            margin-right: 1rem;
            border: 2px solid rgba(255, 215, 0, 0.3);
        }

        .ios-dock-logo img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .ios-nav-link {
            color: rgba(255, 255, 255, 0.8);
            font-weight: 500;
            font-size: 0.95rem;
            padding: 0.5rem 1rem;
            border-radius: 9999px;
            transition: all 0.2s ease;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .ios-nav-link:hover {
            color: #FFD700;
            background: rgba(255, 255, 255, 0.1);
        }
        
        /* New Profile Styles */
        .profile-menu {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding-left: 0.5rem;
        }
        
        .profile-avatar {
            width: 2.5rem; 
            height: 2.5rem;
            border-radius: 50%;
            background: linear-gradient(135deg, #FFD700, #FDB931);
            color: #000;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 0.9rem;
            border: 2px solid rgba(255, 255, 255, 0.2);
            border: 2px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 0 10px rgba(255, 215, 0, 0.3);
            overflow: hidden; /* Ensure image stays within circle */
        }

        /* Ocultar texto en móviles muy pequeños */
        @media (max-width: 640px) {
            .ios-nav-link span {
                display: none;
            }

            .ios-nav-link {
                padding: 0.5rem;
            }

            .ios-dock {
                padding-right: 0.5rem;
            }
        }

        /* --- ESTILOS PARA LA MEJORA DEL LOGIN PROMPT Y CURSOR --- */
        
        /* Modal Premium con efecto Glassmorphism y tonos dorados */
        .premium-modal-backdrop {
            background: rgba(0, 0, 0, 0.85);
            backdrop-filter: blur(8px);
        }

        .premium-modal-content {
            background: linear-gradient(145deg, rgba(20, 20, 20, 0.95), rgba(10, 10, 10, 0.98));
            border: 1px solid rgba(255, 215, 0, 0.3);
            box-shadow: 0 0 40px rgba(255, 215, 0, 0.15), inset 0 0 20px rgba(255, 215, 0, 0.05);
            animation: modalPopIn 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
        }

        @keyframes modalPopIn {
            0% { transform: scale(0.9); opacity: 0; }
            100% { transform: scale(1); opacity: 1; }
        }
    </style>
</head>

<body class="antialiased">

    <!-- HERO HEADER CON VIDEO LLAMATIVO (REQUISITO) -->

    <!-- iOS Dock Navigation -->
    <nav class="ios-dock transition-all duration-500 ease-in-out" id="main-dock">
        <!-- Normal Content -->
        <div id="dock-main-content" class="flex items-center w-full justify-between">
            <div class="ios-dock-logo shrink-0">
            <a href="#">
                <img src="./assets/Logo.png" alt="Trillos Home">
            </a>
        </div>
        <div class="flex items-center space-x-1 sm:space-x-2">
            <!-- Search Trigger -->
            <button onclick="toggleDockSearch()" class="ios-nav-link text-white hover:text-yellow-500 transition-colors" aria-label="Buscar">
                 <i data-lucide="search" class="w-5 h-5"></i>
            </button>
            <div class="h-4 w-px bg-gray-700 mx-1"></div>
            
            <a href="#music" class="ios-nav-link" aria-label="Música">
                <i data-lucide="music" class="w-5 h-5"></i>
                <span>Música</span>
            </a>
            <a href="#videos" class="ios-nav-link" aria-label="Videos">
                <i data-lucide="video" class="w-5 h-5"></i>
                <span>Videos</span>
            </a>
            <a href="#contact" class="ios-nav-link" aria-label="Contacto">
                <i data-lucide="mail" class="w-5 h-5"></i>
                <span>Contacto</span>
            </a>
            <div class="h-4 w-px bg-gray-700 mx-1"></div>
            <?php if (isset($_SESSION['user_name'])): 
                // Generate initials
                $nameParts = explode(' ', $_SESSION['user_name']);
                $initials = '';
                if (count($nameParts) > 0) {
                    $initials .= strtoupper(substr($nameParts[0], 0, 1));
                    if (count($nameParts) > 1) {
                         $initials .= strtoupper(substr($nameParts[count($nameParts) - 1], 0, 1));
                    }
                } else {
                    $initials = 'U';
                }
            ?>

                <div class="relative profile-menu-container">
                    <button onclick="toggleProfileMenu()" class="profile-avatar hover:scale-105 transition-transform" title="<?php echo htmlspecialchars($_SESSION['user_name']); ?>">
                        <?php if ($userProfilePic): ?>
                            <img src="<?php echo htmlspecialchars($userProfilePic); ?>" alt="Profile" class="w-full h-full object-cover">
                        <?php else: ?>
                            <?php echo htmlspecialchars($initials); ?>
                        <?php endif; ?>
                    </button>
                    
                    <!-- Dropdown Menu -->
                    <div id="profile-dropdown" class="hidden absolute right-0 mt-2 w-48 bg-black/90 backdrop-blur-xl border border-yellow-500/20 rounded-xl shadow-2xl py-2 z-50 transform origin-top-right transition-all duration-200">
                        <div class="px-4 py-2 border-b border-gray-800 mb-2">
                            <p class="text-xs text-gray-400">Hola,</p>
                            <p class="text-sm font-bold text-yellow-500 truncate"><?php echo htmlspecialchars($_SESSION['user_name']); ?></p>
                        </div>
                        
                        <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                        <a href="admin.php" class="block px-4 py-2 text-sm text-yellow-500 hover:text-yellow-400 hover:bg-white/10 transition-colors flex items-center">
                            <i data-lucide="shield" class="w-4 h-4 mr-2"></i> Panel Admin
                        </a>
                        <?php endif; ?>

                        <a href="profile.php" class="block px-4 py-2 text-sm text-gray-300 hover:text-white hover:bg-white/10 transition-colors flex items-center">
                            <i data-lucide="user" class="w-4 h-4 mr-2"></i> Mi Perfil
                        </a>
                        <a href="logout.php" class="block px-4 py-2 text-sm text-red-400 hover:text-red-300 hover:bg-white/10 transition-colors flex items-center">
                            <i data-lucide="log-out" class="w-4 h-4 mr-2"></i> Cerrar Sesión
                        </a>
                    </div>
                </div>
            <?php else: ?>
                <a href="login.php" id="login-nav-btn" class="ios-nav-link text-yellow-500 transition-all duration-300" aria-label="Acceso">
                    <i data-lucide="user" class="w-5 h-5"></i>
                    <span class="hidden sm:inline">Acceso</span>
                </a>
            <?php endif; ?>
        </div>
    </div> 

    <!-- Search Container (Initially Hidden) -->
    <div id="dock-search-container" class="hidden w-full flex items-center justify-between" style="min-width: 300px;">
        <div class="relative w-full">
            <i data-lucide="search" class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 w-5 h-5"></i>
            <input type="text" id="dock-search-input" 
                class="w-full bg-transparent border-none rounded-full py-2 pl-10 pr-10 text-white placeholder-gray-400 focus:outline-none focus:ring-0 transition-all font-medium text-lg"
                placeholder="Buscar canciones, videos, artistas...">
            <button onclick="toggleDockSearch()" class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-white">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>
    </div>
    </nav>

    <header class="hero-video-container">
        <!-- Video de fondo (Usamos un placeholder de video de alta calidad) -->
        <!-- NOTA: Reemplazar 'https://...' con el URL de un video loop corto y llamativo propio -->
        <video class="hero-video" autoplay loop muted playsinline
            poster="https://placehold.co/1920x1080/000/FFF?text=Trillos+Visual+Records+Video">
            <!-- Video de cabecera local -->
            <source src="./assets/video_header.mp4" type="video/mp4">
            Tu navegador no soporta la etiqueta de video.
        </video>
        <div class="hero-overlay"></div>
        <div class="hero-content text-center p-8">
            <h1 class="brand-title text-5xl md:text-7xl font-extrabold tracking-tight mb-4 uppercase">
                Trillos Visual Records
            </h1>
            <p class="text-xl md:text-3xl text-gray-300 font-light mb-8 max-w-2xl mx-auto">
                El Aliado de tus momentos | Palabras que inspiran
            </p>
            <!-- Search Bar Removed -->
            <a href="#music" class="inline-flex items-center gap-2 px-8 py-3 bg-[#EAB308] hover:bg-yellow-400 text-black font-bold rounded-full transition-all hover:scale-105 shadow-[0_0_20px_rgba(234,179,8,0.3)]">
                <i data-lucide="headphones" class="w-5 h-5"></i>
                Escucha el Nuevo Sencillo
            </a>
        </div>
    </header>

    <main class="container mx-auto px-4 sm:px-6 lg:px-8">

        <!-- SECCIÓN DE MÚSICA Y CARÁTULAS (REQUISITO) -->
        <section id="music" class="py-16">
            <h2 class="text-4xl brand-title font-bold mb-10 text-center border-b border-yellow-800/50 pb-4">
                Lanzamientos Recientes
            </h2>

            <!-- Search Bar Moved to Hero -->

            <!-- Grid de Carátulas Reproducibles -->
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
                
                <?php if (count($songs) > 0): ?>
                    <?php foreach ($songs as $song): ?>
                    <!-- Tarjeta Dinámica -->
                    <div class="album-card group cursor-pointer relative" 
                        data-id="<?php echo $song['id']; ?>"
                        data-title="<?php echo htmlspecialchars($song['title']); ?>" 
                        data-artist="<?php echo htmlspecialchars($song['artist']); ?>"
                        data-audio-url="<?php echo htmlspecialchars($song['audio_path']); ?>"
                        data-cover="<?php echo htmlspecialchars($song['cover_path']); ?>">
                        <div class="relative overflow-hidden rounded-lg shadow-2xl transition-all duration-500 transform group-hover:scale-[1.02] group-hover:shadow-yellow-900/50">
                            <img src="<?php echo htmlspecialchars($song['cover_path']); ?>" alt="<?php echo htmlspecialchars($song['title']); ?>"
                                class="w-full aspect-square object-cover">
                            <!-- Overlay de Play -->
                            <div class="absolute inset-0 bg-black bg-opacity-40 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity duration-300">
                                <i data-lucide="play-circle" class="w-16 h-16 text-yellow-500 pulsing-play"></i>
                            </div>
                        </div>
                        <!-- Share Button -->
                        <button onclick="event.stopPropagation(); openShareModal('song', <?php echo $song['id']; ?>)" 
                            class="absolute top-2 right-2 p-2 bg-black/60 hover:bg-yellow-500 text-white hover:text-black rounded-full backdrop-blur-md transition-all opacity-0 group-hover:opacity-100 z-30"
                            title="Compartir">
                            <i data-lucide="share-2" class="w-4 h-4"></i>
                        </button>
                        <!-- Like Button -->
                        <?php 
                        $isLiked = in_array($song['id'], $likedSongs);
                        $likeClass = $isLiked ? 'text-red-500' : 'text-white';
                        $fillAttr = $isLiked ? 'fill="currentColor"' : '';
                        ?>
                        <button onclick="event.stopPropagation(); toggleLike('song', <?php echo $song['id']; ?>, this)" 
                            class="absolute top-12 right-2 p-2 bg-black/60 hover:bg-red-500 <?php echo $likeClass; ?> hover:text-white rounded-full backdrop-blur-md transition-all opacity-0 group-hover:opacity-100 z-30"
                            title="Me Gusta">
                            <i data-lucide="heart" class="w-4 h-4" <?php echo $fillAttr; ?>></i>
                        </button>
                        <div class="mt-4 text-center">
                            <p class="text-lg font-semibold text-white truncate"><?php echo htmlspecialchars($song['title']); ?></p>
                            <p class="text-sm text-gray-400"><?php echo htmlspecialchars($song['artist']); ?></p>
                        </div>
                    </div>
                <?php endforeach; ?>
                <?php else: ?>
                    <div class="col-span-full text-center py-10 text-gray-500">
                        <p>No hay canciones disponibles aún.</p>
                    </div>
                <?php endif; ?>

            </div>
        </section>

        <!-- SECCIÓN DE VIDEOS MUSICALES (REQUISITO) -->
        <section id="videos" class="py-16">
            <h2 class="text-4xl brand-title font-bold mb-10 text-center border-b border-yellow-800/50 pb-4">
                Videos Musicales
            </h2>

            <!-- Grid de Videos de YouTube (miniaturas) -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8 max-w-6xl mx-auto">

                <?php if (count($videos) > 0): ?>
                    <?php foreach ($videos as $video): ?>
                    <!-- Video Dinámico -->
                    <div class="video-card cursor-pointer group relative w-full" 
                        data-id="<?php echo $video['id']; ?>"
                        onclick="openVideoModal('<?php echo htmlspecialchars($video['video_path']); ?>', <?php echo $video['id']; ?>)">
                        <div class="relative rounded-lg overflow-hidden shadow-2xl transition-all duration-500 transform group-hover:scale-[1.02] group-hover:shadow-yellow-900/50">
                            <!-- Thumbnail Local -->
                            <img src="<?php echo htmlspecialchars($video['cover_path']); ?>" alt="<?php echo htmlspecialchars($video['title']); ?>"
                                class="w-full aspect-video object-cover transition duration-300 group-hover:opacity-75">
                            <!-- Icono de Play central -->
                            <div class="absolute inset-0 flex items-center justify-center">
                                <i data-lucide="play-circle"
                                    class="w-16 h-16 text-yellow-500 bg-black bg-opacity-70 p-3 rounded-full transition duration-300 group-hover:scale-110"></i>
                            </div>
                        </div>
                        <!-- Share Button -->
                        <button onclick="event.stopPropagation(); openShareModal('video', <?php echo $video['id']; ?>)" 
                            class="absolute top-2 right-2 p-2 bg-black/60 hover:bg-yellow-500 text-white hover:text-black rounded-full backdrop-blur-md transition-all opacity-0 group-hover:opacity-100 z-30"
                            title="Compartir">
                            <i data-lucide="share-2" class="w-4 h-4"></i>
                        </button>
                        <!-- Like Button -->
                        <?php 
                        $isLikedVideo = in_array($video['id'], $likedVideos);
                        $likeClassVideo = $isLikedVideo ? 'text-red-500' : 'text-white';
                        $fillAttrVideo = $isLikedVideo ? 'fill="currentColor"' : '';
                        ?>
                        <button onclick="event.stopPropagation(); toggleLike('video', <?php echo $video['id']; ?>, this)" 
                            class="absolute top-12 right-2 p-2 bg-black/60 hover:bg-red-500 <?php echo $likeClassVideo; ?> hover:text-white rounded-full backdrop-blur-md transition-all opacity-0 group-hover:opacity-100 z-30"
                            title="Me Gusta">
                            <i data-lucide="heart" class="w-4 h-4" <?php echo $fillAttrVideo; ?>></i>
                        </button>
                        <p class="mt-3 text-lg font-semibold text-white text-center truncate"><?php echo htmlspecialchars($video['title']); ?></p>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="col-span-full text-center py-10 text-gray-500">
                        <p>No hay videos disponibles aún.</p>
                    </div>
                <?php endif; ?>

            </div>
        </section>

        <!-- SECCIÓN DE COMPARTIR Y CONTACTO -->
        <section id="contact" class="py-16 text-center">
            <h2 class="text-3xl brand-title font-bold mb-6">
                Conecta con Trillos Visual Records
            </h2>
            <p class="text-lg mb-8 text-gray-400">Síguenos y comparte nuestra música con el mundo.</p>

            <!-- Botones de Redes Sociales -->
            <div class="flex justify-center space-x-4 mb-8">
                <!-- Facebook -->
                <a href="https://www.facebook.com/suspirosqueenloquecen/" target="_blank" class="share-button p-3 rounded-full bg-gray-800 text-yellow-500 flex items-center hover:bg-white/10 transition-colors">
                    <i data-lucide="facebook" class="w-6 h-6"></i>
                </a>
                <!-- Instagram (Replaces Twitter) -->
                <a href="https://www.instagram.com/trillos_visual_records/" target="_blank" class="share-button p-3 rounded-full bg-gray-800 text-yellow-500 flex items-center hover:bg-white/10 transition-colors">
                    <i data-lucide="instagram" class="w-6 h-6"></i>
                </a>
                <!-- WhatsApp -->
                <a href="https://wa.me/50765763880" target="_blank" class="share-button p-3 rounded-full bg-gray-800 text-yellow-500 flex items-center hover:bg-white/10 transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-6 h-6">
                        <path d="M3 21l1.65-3.8a9 9 0 1 1 3.4 2.9L3 21" />
                        <path d="M9 10a.5.5 0 0 0 1 0V9a.5.5 0 0 0-1 0v1a5 5 0 0 0 5 5h1a.5.5 0 0 0 0-1h-1a.5.5 0 0 0 0 1" />
                    </svg>
                </a>

            </div>

            <p class="text-sm text-gray-500">Para consultas: <a href="mailto:narracionesinsitus@gmail.com"
                    class="text-yellow-600 hover:text-yellow-400 transition-colors">narracionesinsitus@gmail.com</a>
            </p>
        </section>

    </main>

    <!-- REPRODUCTOR DE AUDIO GLOBAL FIJO (REQUISITO: Audios Reproducibles) -->
    <!-- REPRODUCTOR DE AUDIO GLOBAL FIJO (REQUISITO: Audios Reproducibles) -->
    <!-- REPRODUCTOR DE AUDIO GLOBAL FIJO (REQUISITO: Audios Reproducibles) -->
    <div id="audio-player-bar" class="hidden p-4 flex flex-col md:flex-row items-center justify-between gap-4 overflow-visible rounded-t-2xl border-t border-white/5 shadow-[0_-4px_20px_rgba(0,0,0,0.5)] transition-transform duration-500 transform -translate-x-1/2">
        <div class="flex items-center space-x-4 w-full md:w-auto justify-start">
            <!-- Carátula pequeña -->
            <img id="player-cover" src="https://placehold.co/60x60/333/999?text=Play" alt="Carátula"
                class="w-12 h-12 rounded-lg shadow-lg border border-yellow-900/30">
            <!-- Título y Artista -->
            <div class="overflow-hidden">
                <p id="player-title" class="text-sm font-bold text-white truncate w-full">Selecciona una canción</p>
                <p id="player-artist" class="text-xs text-yellow-500/80">Trillos Visual Records</p>
            </div>
        </div>

        <!-- Barra de Progreso y Controles -->
        <div class="flex flex-col w-full md:flex-1 px-0 md:px-8">
            <div class="flex items-center justify-between mb-1 text-xs text-gray-400 font-mono">
                <span id="current-time">0:00</span>
                <span id="total-time">0:00</span>
            </div>

            <div class="waveform-container">
                <div class="waveform-bg"></div>
                <div id="waveform-active" class="waveform-active"></div>
                <input type="range" id="seek-bar" value="0" min="0" max="100" class="waveform-input">
            </div>

            <!-- Controles centrados debajo de la onda (en móvil) o al lado? No, diseño: Controles en medio? -->
            <!-- Vamos a poner los controles en una fila aparte o integrados. Diseño actual: Onda arriba, controles abajo o al lado. 
                 Vamos a mantener un layout limpio: Izq: Info, Centro: Controles + Onda, Der: Volumen -->
        </div>

        <!-- Controles de Audio -->
        <div class="flex items-center justify-center space-x-6 w-full md:w-auto order-first md:order-none mb-2 md:mb-0">
            <button id="prev-btn" class="text-gray-400 hover:text-white transition-colors"><i data-lucide="skip-back"
                    class="w-5 h-5"></i></button>
            <button id="play-pause-btn"
                class="text-black bg-yellow-500 hover:bg-yellow-400 p-3 rounded-full transition-all transform hover:scale-105 shadow-[0_0_15px_rgba(255,215,0,0.4)]">
                <i id="play-pause-icon" data-lucide="play" class="w-6 h-6 fill-current"></i>
            </button>
            <button id="next-btn" class="text-gray-400 hover:text-white transition-colors"><i data-lucide="skip-forward"
                    class="w-5 h-5"></i></button>
        </div>

        <!-- Volumen -->
        <div class="hidden lg:flex items-center space-x-3 min-w-[120px]">
            <i data-lucide="volume-2" class="w-4 h-4 text-gray-400"></i>
            <input type="range" id="volume-bar" value="100" min="0" max="100"
                class="w-full h-1 bg-gray-700 rounded-lg appearance-none cursor-pointer accent-yellow-500">
        </div>

        <!-- Close/Hide Button -->
        <!-- Close/Hide Button -->
        <button id="toggle-player-btn" onclick="togglePlayer()" class="absolute -top-5 left-1/2 transform -translate-x-1/2 bg-black border border-yellow-500/30 rounded-full p-2 text-white hover:bg-yellow-500 hover:text-black transition-all shadow-[0_-5px_15px_rgba(0,0,0,0.5)] z-50 group" title="Mostrar/Ocultar Reproductor">
             <i data-lucide="chevron-down" class="w-6 h-6 group-hover:animate-bounce"></i>
        </button>
    </div>

    <!-- VENTANA MODAL PARA VIDEOS (REQUISITO: 1 sola ventana) -->
    <div id="video-modal"
    <div id="video-modal"
        class="fixed inset-0 bg-black bg-opacity-90 hidden flex items-center justify-center z-[2100] transition-opacity duration-300 opacity-0"
        onclick="closeVideoModal()">
        <div class="relative w-11/12 max-w-4xl rounded-xl overflow-hidden shadow-2xl" onclick="event.stopPropagation()">
            <button class="absolute top-4 right-4 text-white hover:text-yellow-500 z-10" onclick="closeVideoModal()">
                <i data-lucide="x" class="w-8 h-8"></i>
            </button>
            <div id="video-iframe-container" class="aspect-video bg-black flex items-center justify-center">
                 <video id="video-player" controls class="w-full h-full max-h-[80vh]">
                    <source src="" type="video/mp4">
                    Tu navegador no soporta videos HTML5.
                </video>
            </div>
        </div>
    </div>

    <!-- MODAL DE MENSAJES PREMIUM (Sustituye a alert/confirm) -->
    <div id="message-modal"
        class="fixed inset-0 premium-modal-backdrop hidden flex items-center justify-center z-[3000] transition-opacity duration-300 opacity-0">
        <div class="premium-modal-content p-8 rounded-2xl max-w-sm w-full relative overflow-hidden text-center">
             <!-- Decoración de fondo -->
             <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-transparent via-yellow-500 to-transparent"></div>
             
             <div class="mb-5 inline-flex p-4 rounded-full bg-yellow-900/20 text-yellow-500 mb-4 ring-1 ring-yellow-500/50">
                <i data-lucide="lock" class="w-10 h-10"></i>
             </div>
             
            <h3 id="modal-title" class="text-2xl font-bold text-yellow-500 mb-2 font-display">Acceso Requerido</h3>
            <p id="modal-text" class="text-gray-300 text-base leading-relaxed mb-6"></p>
            
            <div class="flex flex-col gap-3">
                <a href="login.php" class="w-full bg-yellow-500 hover:bg-yellow-400 text-black font-bold py-3 px-6 rounded-xl transition-all shadow-lg hover:shadow-yellow-500/20 transform hover:-translate-y-1">
                    Iniciar Sesión
                </a>
                <button onclick="closeMessageModal()"
                    class="w-full bg-transparent border border-gray-700 hover:border-gray-500 text-gray-400 hover:text-white font-medium py-3 px-6 rounded-xl transition-colors">
                    Cancelar
                </button>
            </div>
        </div>
    </div>

    <!-- SHARE MODAL (Social & Copy) -->
    <div id="share-modal" class="fixed inset-0 bg-black/90 backdrop-blur-md hidden flex items-center justify-center z-[2200] transition-opacity duration-300 opacity-0" onclick="closeShareModal()">
        <div class="relative w-full max-w-sm bg-[#111] border border-white/10 rounded-2xl p-6 shadow-2xl transform transition-all scale-95 opacity-0" id="share-modal-content" onclick="event.stopPropagation()">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-xl font-bold text-white flex items-center gap-2">
                    <i data-lucide="share-2" class="w-5 h-5 text-yellow-500"></i> Compartir
                </h3>
                <button onclick="closeShareModal()" class="text-gray-400 hover:text-white transition-colors">
                    <i data-lucide="x" class="w-6 h-6"></i>
                </button>
            </div>

            <div class="grid grid-cols-4 gap-4 mb-6">
                <!-- WhatsApp -->
                <button onclick="shareSocial('whatsapp')" class="flex flex-col items-center gap-2 group">
                    <div class="w-12 h-12 rounded-full bg-[#25D366]/20 flex items-center justify-center text-[#25D366] group-hover:bg-[#25D366] group-hover:text-white transition-all">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-message-circle"><path d="M7.9 20A9 9 0 1 0 4 16.1L2 22Z"/></svg>
                    </div>
                    <span class="text-xs text-gray-400 group-hover:text-white">WhatsApp</span>
                </button>
                <!-- Facebook -->
                <button onclick="shareSocial('facebook')" class="flex flex-col items-center gap-2 group">
                    <div class="w-12 h-12 rounded-full bg-[#1877F2]/20 flex items-center justify-center text-[#1877F2] group-hover:bg-[#1877F2] group-hover:text-white transition-all">
                        <i data-lucide="facebook" class="w-6 h-6"></i>
                    </div>
                    <span class="text-xs text-gray-400 group-hover:text-white">Facebook</span>
                </button>

                <!-- Instagram -->
                <button onclick="shareSocial('instagram')" class="flex flex-col items-center gap-2 group">
                    <div class="w-12 h-12 rounded-full bg-[#E1306C]/20 flex items-center justify-center text-[#E1306C] group-hover:bg-[#E1306C] group-hover:text-white transition-all">
                        <i data-lucide="instagram" class="w-6 h-6"></i>
                    </div>
                    <span class="text-xs text-gray-400 group-hover:text-white">Instagram</span>
                </button>
                <!-- Copy Link -->
                <button onclick="shareSocial('copy')" class="flex flex-col items-center gap-2 group">
                    <div class="w-12 h-12 rounded-full bg-yellow-500/20 flex items-center justify-center text-yellow-500 group-hover:bg-yellow-500 group-hover:text-black transition-all">
                        <i data-lucide="link" class="w-6 h-6"></i>
                    </div>
                    <span class="text-xs text-gray-400 group-hover:text-white">Copiar</span>
                </button>
            </div>

            <!-- Input readonly with link -->
            <div class="relative">
                <input type="text" id="share-link-input" readonly class="w-full bg-black/50 border border-gray-800 text-gray-400 text-sm rounded-lg px-4 py-3 pr-12 focus:outline-none focus:border-yellow-500/50 transition-colors">
                <button onclick="shareSocial('copy')" class="absolute right-2 top-1/2 -translate-y-1/2 p-1.5 text-gray-500 hover:text-white transition-colors">
                    <i data-lucide="copy" class="w-4 h-4"></i>
                </button>
            </div>
        </div>
    </div>
    
    <!-- ELEMENTO CURSOR FALSO (ELIMINADO) -->

    <audio id="global-audio" preload="auto"></audio>

    <script>
        // Inicializa los iconos de Lucide
        lucide.createIcons();

        // Pass PHP data to JS for clientside search
        const allSongs = <?php echo json_encode($songs); ?>;
        const allVideos = <?php echo json_encode($videos); ?>;
        const isLoggedIn = <?php echo isset($_SESSION['user_id']) ? 'true' : 'false'; ?>;

        // -------------------------
        // VARIABLES GLOBALES DE AUDIO
        // -------------------------
        const audio = document.getElementById('global-audio');
        const playPauseBtn = document.getElementById('play-pause-btn');
        const playerTitle = document.getElementById('player-title');
        const playerArtist = document.getElementById('player-artist');
        const playerCover = document.getElementById('player-cover');
        const seekBar = document.getElementById('seek-bar');
        const currentTimeEl = document.getElementById('current-time');
        const totalTimeEl = document.getElementById('total-time');
        const volumeBar = document.getElementById('volume-bar');
        const albumCards = document.querySelectorAll('.album-card');

        let isPlaying = false;
        let currentTrack = null;

        // -------------------------
        // FUNCIONES DE UTILIDAD
        // -------------------------
        function showMessage(text, title = 'Acceso Requerido') {
            const modalTitle = document.getElementById('modal-title');
            const modalText = document.getElementById('modal-text');
            if(modalTitle) modalTitle.textContent = title;
            if(modalText) modalText.innerHTML = text;
            const modal = document.getElementById('message-modal');
            if(modal) {
                modal.classList.remove('hidden');
                setTimeout(() => modal.classList.add('opacity-100'), 10);
            } else {
                alert(text);
            }
        }

        function closeMessageModal() {
            const modal = document.getElementById('message-modal');
            if(modal) {
                modal.classList.remove('opacity-100');
                setTimeout(() => modal.classList.add('hidden'), 300);
            }
        }

        function formatTime(seconds) {
            if(!seconds) return '0:00';
            const minutes = Math.floor(seconds / 60);
            const secs = Math.floor(seconds % 60);
            return `${minutes}:${secs < 10 ? '0' : ''}${secs}`;
        }

        // -------------------------
        // CONTROL DE REPRODUCTOR
        // -------------------------
        // -------------------------
        // FUNCIONES DE PERFIL (Injected)
        // -------------------------
        function addToHistory(type, id) {
             if (typeof isLoggedIn !== 'undefined' && !isLoggedIn) return;
             const formData = new FormData();
             formData.append('action', 'add_history');
             formData.append('type', type);
             formData.append('id', id);
             fetch('api_profile.php', { method: 'POST', body: formData }).catch(e => console.error(e));
        }

        function toggleLike(type, id, btn) {
            if (!isLoggedIn) {
                showMessage('Debes iniciar sesión para dar Me Gusta.', '¡Únete a la Comunidad!');
                return;
            }
            const icon = btn.querySelector('svg') || btn.querySelector('i');
            const isLiked = btn.classList.contains('text-red-500');
            
            // UI Optimista
            if(isLiked) {
                btn.classList.remove('text-red-500');
                btn.classList.add('text-white');
                if(icon) icon.setAttribute('fill', 'none'); 
            } else {
                btn.classList.add('text-red-500');
                btn.classList.remove('text-white');
                if(icon) icon.setAttribute('fill', 'currentColor');
            }

            const formData = new FormData();
            formData.append('action', 'toggle_like');
            formData.append('type', type);
            formData.append('id', id);
            
            fetch('api_profile.php', { method: 'POST', body: formData })
            .then(res => res.json())
            .then(data => {
                if(!data.success) {
                    // Revertir
                     if(isLiked) {
                        btn.classList.add('text-red-500');
                        btn.classList.remove('text-white');
                         if(icon) icon.setAttribute('fill', 'currentColor');
                    } else {
                        btn.classList.remove('text-red-500');
                        btn.classList.add('text-white');
                         if(icon) icon.setAttribute('fill', 'none'); 
                    }
                }
            })
            .catch(e => console.error(e));
        }

        function loadAndPlayTrack(card) {
            if (!isLoggedIn) {
                showMessage('Para disfrutar de la música de alta calidad de <strong>Trillos Records</strong>, por favor inicia sesión.');
                return;
            }

            const title = card.dataset.title || (card.querySelector('.font-bold') ? card.querySelector('.font-bold').textContent : '');
            const artist = card.dataset.artist || (card.querySelector('.text-xs') ? card.querySelector('.text-xs').textContent : '');
            const audioUrl = card.dataset.audioUrl || card.getAttribute('data-audio-url');
            const cover = card.dataset.cover || (card.querySelector('img') ? card.querySelector('img').src : '');

             if(!audioUrl) return;

            if (currentTrack === audioUrl) {
                togglePlayPause();
                return;
            }

            // Registrar Historial
            const songId = card.dataset.id;
            if (songId) addToHistory('song', songId);

            // Show Player
            const playerBar = document.getElementById('audio-player-bar');
            if(playerBar) {
                playerBar.classList.remove('hidden', 'translate-y-full', 'translate-y-[130%]', 'translate-y-[calc(100%_+_1.5rem)]');
                const toggleBtn = document.getElementById('toggle-player-btn');
                if(toggleBtn) {
                     toggleBtn.innerHTML = '<i data-lucide="chevron-down" class="w-6 h-6 group-hover:animate-bounce"></i>';
                     toggleBtn.title = "Ocultar Reproductor";
                     lucide.createIcons();
                }
            }

            if(playerTitle) playerTitle.textContent = title;
            if(playerArtist) playerArtist.textContent = artist;
            if(playerCover) playerCover.src = cover;
            audio.src = audioUrl;
            currentTrack = audioUrl;

            updatePlayPauseIcon('pause');
            audio.play().catch(e => {
                console.warn('Autoplay prevented', e);
                updatePlayPauseIcon('play');
            });
            isPlaying = true;
        }

        function updatePlayPauseIcon(iconName) {
            if(playPauseBtn) {
                playPauseBtn.innerHTML = `<i data-lucide="${iconName}" class="w-6 h-6 fill-current"></i>`;
                lucide.createIcons();
            }
        }

        function togglePlayPause() {
            if (!isLoggedIn) {
                showMessage('Por favor inicia sesión para usar el control de reproducción.');
                return;
            }
            if (!currentTrack) {
                 if(albumCards.length > 0) loadAndPlayTrack(albumCards[0]);
                return;
            }
            if (audio.paused) {
                audio.play();
                updatePlayPauseIcon('pause');
            } else {
                audio.pause();
                updatePlayPauseIcon('play');
            }
            isPlaying = !audio.paused;
        }

        function togglePlayer() {
            const playerBar = document.getElementById('audio-player-bar');
            const toggleBtn = document.getElementById('toggle-player-btn');
            
            if(playerBar && toggleBtn) {
                const hiddenClass = 'translate-y-[calc(100%_+_1.5rem)]';
                const isHidden = playerBar.classList.contains(hiddenClass);
                
                if (isHidden) {
                    playerBar.classList.remove(hiddenClass);
                    toggleBtn.innerHTML = '<i data-lucide="chevron-down" class="w-6 h-6 group-hover:animate-bounce"></i>';
                    toggleBtn.title = "Ocultar Reproductor";
                } else {
                    playerBar.classList.add(hiddenClass);
                    toggleBtn.innerHTML = '<i data-lucide="chevron-up" class="w-6 h-6 group-hover:animate-bounce"></i>';
                    toggleBtn.title = "Mostrar Reproductor";
                }
                lucide.createIcons();
            }
        }

        // Audio Events
        audio.addEventListener('loadedmetadata', () => {
            if(totalTimeEl) totalTimeEl.textContent = formatTime(audio.duration);
            if(seekBar) seekBar.max = audio.duration;
        });

        audio.addEventListener('timeupdate', () => {
            if(currentTimeEl) currentTimeEl.textContent = formatTime(audio.currentTime);
            if(seekBar) seekBar.value = audio.currentTime;
            
            const waveformActive = document.getElementById('waveform-active');
            if(waveformActive) {
                const progressPercent = (audio.currentTime / audio.duration) * 100;
                waveformActive.style.width = `${progressPercent}%`;
            }
        });

        if(seekBar) {
            seekBar.addEventListener('input', () => {
                audio.currentTime = seekBar.value;
            });
        }
        
        if(volumeBar) {
            volumeBar.addEventListener('input', () => {
                audio.volume = volumeBar.value / 100;
            });
        }
        
        if(document.getElementById('prev-btn')) document.getElementById('prev-btn').addEventListener('click', () => {/* Logic for prev */});
        if(document.getElementById('next-btn')) document.getElementById('next-btn').addEventListener('click', () => {/* Logic for next */});
        if(playPauseBtn) playPauseBtn.addEventListener('click', togglePlayPause);
        
        albumCards.forEach(card => card.addEventListener('click', () => loadAndPlayTrack(card)));


        // -------------------------
        // DOCK SEARCH LOGIC
        // -------------------------
        function toggleDockSearch() {
            const dock = document.getElementById('main-dock');
            const mainContent = document.getElementById('dock-main-content');
            const searchContainer = document.getElementById('dock-search-container');
            const searchInput = document.getElementById('dock-search-input');

            if (searchContainer.classList.contains('hidden')) {
                mainContent.classList.add('hidden');
                searchContainer.classList.remove('hidden');
                dock.classList.add('w-[95vw]', 'max-w-3xl'); 
                setTimeout(() => searchInput.focus(), 50);
            } else {
                searchContainer.classList.add('hidden');
                mainContent.classList.remove('hidden');
                 dock.classList.remove('w-[95vw]', 'max-w-3xl');
                 closeSearchModal();
                 searchInput.value = '';
            }
        }

        document.addEventListener('DOMContentLoaded', () => {
            // Check for URL params to auto-play
            const urlParams = new URLSearchParams(window.location.search);
            const songId = urlParams.get('song');
            const videoId = urlParams.get('video');

            if (songId) {
                const song = allSongs.find(s => s.id == songId);
                if (song) {
                    // Create a dummy element to pass to loadAndPlayTrack or call logic directly
                    // Since loadAndPlayTrack expects an element with data attributes, let's look for one or create mock
                    // Easier: just set the player state manually if function is coupled to DOM
                    // Check if there is a DOM element for this song (e.g. in search results or hidden list?)
                    // Actually, we can just reproduce loadAndPlayTrack logic here for clean variable access:
                    loadAndPlayTrack({
                        dataset: {
                            title: song.title,
                            artist: song.artist,
                            cover: song.cover_path,
                            audioUrl: song.audio_path,
                            id: song.id
                        },
                        getAttribute: () => null // Prevent error if logic falls back
                    });
                }
            } else if (videoId) {
                const video = allVideos.find(v => v.id == videoId);
                if (video) {
                    openVideoModal(video.video_path, video.id);
                }
            }

            const searchInput = document.getElementById('dock-search-input');
            if(searchInput) {
                searchInput.addEventListener('input', (e) => {
                    const query = e.target.value.toLowerCase().trim();
                    const modal = document.getElementById('search-modal');
                    
                    if (query.length === 0) {
                        closeSearchModal();
                        return;
                    }
                    
                    const matchedSongs = allSongs.filter(s => (s.title || '').toLowerCase().includes(query) || (s.artist || '').toLowerCase().includes(query));
                    const matchedVideos = allVideos.filter(v => (v.title || '').toLowerCase().includes(query) || (v.artist || '').toLowerCase().includes(query));
                    
                    renderSearchResults(matchedSongs, matchedVideos);
                    
                    if(modal && (modal.classList.contains('hidden') || modal.classList.contains('opacity-0'))) {
                         openSearchModal();
                    }
                });
            }
        });


        function openSearchModal() {
            const searchModal = document.getElementById('search-modal');
            if(searchModal) {
                searchModal.classList.remove('hidden');
                setTimeout(() => {
                     searchModal.classList.remove('opacity-0');
                     const content = document.getElementById('search-modal-content');
                     if(content) {
                         content.classList.remove('opacity-0', 'scale-95');
                         content.classList.add('scale-100');
                     }
                }, 10);
            }
        }

        function closeSearchModal() {
            const searchModal = document.getElementById('search-modal');
            if(searchModal) {
                 searchModal.classList.add('opacity-0');
                 const content = document.getElementById('search-modal-content');
                 if(content) content.classList.add('opacity-0', 'scale-95');
                 setTimeout(() => {
                     searchModal.classList.add('hidden');
                 }, 300);
            }
        }
        
        function renderSearchResults(songs, videos) {
            let html = '';
            
            if (songs.length > 0) {
                html += '<h3 class="text-xl font-bold text-white mb-4 pl-2 border-l-4 border-yellow-500">Canciones</h3>';
                html += '<div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-8">';
                songs.forEach(song => {
                     html += `
                        <div class="bg-white/5 p-3 rounded-lg flex items-center gap-3 hover:bg-white/10 transition cursor-pointer" 
                             data-title="${song.title}" data-artist="${song.artist}" data-audio-url="${song.audio_path}" data-cover="${song.cover_path}" data-id="${song.id}"
                             onclick="closeSearchModal(); loadAndPlayTrack(this)">
                            <img src="${song.cover_path}" class="w-12 h-12 rounded object-cover">
                            <div>
                                <p class="font-bold text-white text-sm">${song.title}</p>
                                <p class="text-xs text-gray-400">${song.artist}</p>
                            </div>
                        </div>
                     `;
                });
                html += '</div>';
            }
            
            if (videos.length > 0) {
                html += '<h3 class="text-xl font-bold text-white mb-4 pl-2 border-l-4 border-yellow-500">Videos</h3>';
                html += '<div class="grid grid-cols-1 md:grid-cols-2 gap-4">';
                videos.forEach(video => {
                     html += `
                        <div class="bg-white/5 p-3 rounded-lg flex items-center gap-3 hover:bg-white/10 transition cursor-pointer" onclick="closeSearchModal(); openVideoModal('${video.video_path}', ${video.id})">
                            <img src="${video.cover_path}" class="w-16 h-10 rounded object-cover">
                            <div>
                                <p class="font-bold text-white text-sm">${video.title}</p>
                                <p class="text-xs text-gray-400">Video Musical</p>
                            </div>
                        </div>
                     `;
                });
                html += '</div>';
            }
            
            if (html === '') {
                html = '<div class="text-center py-10"><p class="text-gray-500 text-lg">No se encontraron resultados.</p></div>';
            }

            const searchResultsContainer = document.getElementById('search-results-container');
            if(searchResultsContainer) {
                searchResultsContainer.innerHTML = html;
                lucide.createIcons();
            }
        }

        function toggleProfileMenu() {
            const el = document.getElementById('profile-dropdown');
            if(el) el.classList.toggle('hidden');
        }
        
        function openVideoModal(path, id) {
             if(id) addToHistory('video', id);
             const modal = document.getElementById('video-modal');
             const player = document.getElementById('video-player');
             if(player) {
                 const source = player.querySelector('source');
                 if(source) source.src = path;
                 player.load();
                 player.play().catch(e => console.log('Autoplay blocked', e));
             }
             if(modal) {
                 modal.classList.remove('hidden');
                 setTimeout(() => modal.classList.remove('opacity-0'), 10);
             }
        }
        
        function closeVideoModal() {
            const modal = document.getElementById('video-modal');
            const player = document.getElementById('video-player');
            if(player) player.pause();
            if(modal) {
                modal.classList.add('opacity-0');
                setTimeout(() => modal.classList.add('hidden'), 300);
            }
        }

        function openShareModal(type, id) {
             if (typeof isLoggedIn !== 'undefined' && !isLoggedIn) {
                showMessage('Debes iniciar sesión para compartir contenido exclusivo.', '¡Únete a la Comunidad!');
                return;
             }
             const modal = document.getElementById('share-modal');
             const content = document.getElementById('share-modal-content');
             if(modal) {
                 modal.classList.remove('hidden');
                 // Force reflow
                 void modal.offsetWidth;
                 modal.classList.remove('opacity-0');
                 
                 if(content) {
                     content.classList.remove('scale-95', 'opacity-0');
                     content.classList.add('scale-100', 'opacity-100');
                 }
                 lucide.createIcons();
             }
             const input = document.getElementById('share-link-input');
             if(input) input.value = window.location.href + '?' + type + '=' + id;
        }
        
        function closeShareModal() {
             const modal = document.getElementById('share-modal');
             const content = document.getElementById('share-modal-content');
             
             if(content) {
                 content.classList.remove('scale-100', 'opacity-100');
                 content.classList.add('scale-95', 'opacity-0');
             }

             if(modal) {
                 modal.classList.add('opacity-0');
                 setTimeout(() => modal.classList.add('hidden'), 300);
             }
        }
        
        function shareSocial(platform) {
            const input = document.getElementById('share-link-input');
            const url = input ? input.value : window.location.href;
            
            if(platform === 'copy') {
                 navigator.clipboard.writeText(url).then(() => {
                     // Feedback visual
                     const copyBtn = document.querySelector('button[onclick="shareSocial(\'copy\')"]');
                     if(copyBtn) {
                         const originalContent = copyBtn.innerHTML;
                         copyBtn.innerHTML = `
                            <div class="w-12 h-12 rounded-full bg-green-500/20 flex items-center justify-center text-green-500 transition-all">
                                <i data-lucide="check" class="w-6 h-6"></i>
                            </div>
                            <span class="text-xs text-white">¡Copiado!</span>
                         `;
                         lucide.createIcons();
                         setTimeout(() => {
                             copyBtn.innerHTML = originalContent;
                             lucide.createIcons();
                         }, 2000);
                     }
                 });
                 return;
            }
            if(platform === 'whatsapp') {
                window.open(`https://api.whatsapp.com/send?text=${encodeURIComponent(url)}`, '_blank');
            }
            if(platform === 'facebook') {
                window.open(`https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(url)}`, '_blank');
            }
            if(platform === 'telegram') {
                window.open(`https://t.me/share/url?url=${encodeURIComponent(url)}&text=${encodeURIComponent(document.title)}`, '_blank');
            }
        }
        
        function copyLink() {
            navigator.clipboard.writeText(window.location.href);
        }

        async function toggleLike(type, id, btn) {
            if(!isLoggedIn) { showMessage('Inicia sesión para esto'); return; }
            // Add actual fetch call if needed, simplified for safe restore
            // The original had full fetch logic
            const fd = new FormData();
            fd.append('action', 'toggle_like');
            fd.append('type', type);
            fd.append('item_id', id);
            try {
                const res = await fetch('api_profile.php', { method: 'POST', body: fd });
                const data = await res.json();
                if(data.success && btn) {
                     const icon = btn.querySelector('i');
                     if(data.liked) {
                         btn.classList.add('bg-red-500');
                         if(icon) icon.setAttribute('fill', 'currentColor');
                     } else {
                         btn.classList.remove('bg-red-500');
                         if(icon) icon.setAttribute('fill', 'none');
                     }
                }
            } catch(e) {}
        }
        
        async function addToHistory(type, id) {
             const fd = new FormData();
             fd.append('action', 'add_history');
             fd.append('type', type);
             fd.append('item_id', id);
             fetch('api_profile.php', { method: 'POST', body: fd });
        }
    </script>

    <!-- Global Search Results Modal (Styled like Profile) -->
    <div id="share-modal" class="fixed inset-0 bg-black/90 hidden z-[3000] flex items-center justify-center opacity-0 transition-opacity duration-300">
        <div id="share-modal-content" class="bg-[#111] border border-gray-800 rounded-2xl p-6 max-w-sm w-full transform scale-95 opacity-0 transition-all duration-300 relative">
            <button onclick="closeShareModal()" class="absolute top-4 right-4 text-gray-400 hover:text-white">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
            <h3 class="text-xl font-bold text-white mb-2 text-center">Compartir</h3>
            <p class="text-gray-400 text-sm text-center mb-6">Comparte este contenido con tus amigos</p>
            
            <div class="grid grid-cols-4 gap-4 mb-6">
                <!-- WhatsApp -->
                <button onclick="shareSocial('whatsapp')" class="flex flex-col items-center gap-2 group">
                    <div class="w-12 h-12 rounded-full bg-[#25D366]/20 flex items-center justify-center text-[#25D366] group-hover:bg-[#25D366] group-hover:text-white transition-all">
                        <i data-lucide="message-circle" class="w-6 h-6"></i>
                    </div>
                    <span class="text-xs text-gray-400 group-hover:text-white">WhatsApp</span>
                </button>
                <!-- Facebook -->
                <button onclick="shareSocial('facebook')" class="flex flex-col items-center gap-2 group">
                    <div class="w-12 h-12 rounded-full bg-[#1877F2]/20 flex items-center justify-center text-[#1877F2] group-hover:bg-[#1877F2] group-hover:text-white transition-all">
                        <i data-lucide="facebook" class="w-6 h-6"></i>
                    </div>
                    <span class="text-xs text-gray-400 group-hover:text-white">Facebook</span>
                </button>
                <!-- Telegram -->
                <button onclick="shareSocial('telegram')" class="flex flex-col items-center gap-2 group">
                    <div class="w-12 h-12 rounded-full bg-[#229ED9]/20 flex items-center justify-center text-[#229ED9] group-hover:bg-[#229ED9] group-hover:text-white transition-all">
                        <i data-lucide="send" class="w-6 h-6"></i>
                    </div>
                    <span class="text-xs text-gray-400 group-hover:text-white">Telegram</span>
                </button>
                <!-- Copy Link -->
                <button onclick="shareSocial('copy')" class="flex flex-col items-center gap-2 group">
                    <div class="w-12 h-12 rounded-full bg-yellow-500/20 flex items-center justify-center text-yellow-500 group-hover:bg-yellow-500 group-hover:text-black transition-all">
                        <i data-lucide="link" class="w-6 h-6"></i>
                    </div>
                    <span class="text-xs text-gray-400 group-hover:text-white">Copiar</span>
                </button>
            </div>

            <!-- Input readonly with link -->
            <div class="relative">
                <input type="text" id="share-link-input" readonly class="w-full bg-black/50 border border-gray-800 text-gray-400 text-sm rounded-lg px-4 py-3 pr-12 focus:outline-none focus:border-yellow-500/50 transition-colors">
                <button onclick="shareSocial('copy')" class="absolute right-2 top-1/2 -translate-y-1/2 p-1.5 text-gray-500 hover:text-white transition-colors">
                    <i data-lucide="copy" class="w-4 h-4"></i>
                </button>
            </div>
        </div>
    </div>

    <div id="search-modal" class="fixed inset-0 bg-black/90 hidden z-[2000] flex items-start justify-center pt-28 opacity-0 transition-opacity duration-300">
        <!-- Modal Content -->
        <div id="search-modal-content" class="bg-[#111] border border-gray-800 rounded-2xl p-6 max-w-4xl w-full h-[80vh] flex flex-col transform scale-95 opacity-0 transition-all duration-300 relative shadow-2xl shadow-yellow-900/20">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-2xl font-bold text-yellow-500 brand-title">Resultados de Búsqueda</h2>
                <button onclick="closeSearchModal()" class="text-gray-400 hover:text-white transition">
                    <i data-lucide="x" class="w-6 h-6"></i>
                </button>
            </div>

            <!-- Scrollable Results -->
            <div id="search-results-container" class="flex-1 overflow-y-auto custom-scrollbar pb-4 space-y-6 pr-2">
                <!-- Results will be injected here -->
            </div>
        </div>
    </div>
</body>

</html>
