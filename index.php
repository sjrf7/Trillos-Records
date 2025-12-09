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
            height: calc(80vh + 60px);
            /* Altura generosa para el video */
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 4rem;
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
            transform: translateX(-50%);
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
            z-index: 1000;
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
            box-shadow: 0 0 10px rgba(255, 215, 0, 0.3);
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
    <nav class="ios-dock">
        <div class="ios-dock-logo">
            <a href="#">
                <img src="./Nueva carpeta/Logo.png" alt="Trillos Home">
            </a>
        </div>
        <div class="flex items-center space-x-1 sm:space-x-2">
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
                        <?php echo htmlspecialchars($initials); ?>
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
    </nav>

    <header class="hero-video-container">
        <!-- Video de fondo (Usamos un placeholder de video de alta calidad) -->
        <!-- NOTA: Reemplazar 'https://...' con el URL de un video loop corto y llamativo propio -->
        <video class="hero-video" autoplay loop muted playsinline
            poster="https://placehold.co/1920x1080/000/FFF?text=Trillos+Visual+Records+Video">
            <!-- Video de cabecera local -->
            <source src="./Nueva carpeta/video_header.mp4" type="video/mp4">
            Tu navegador no soporta la etiqueta de video.
        </video>
        <div class="hero-overlay"></div>
        <div class="hero-content text-center p-8">
            <h1 class="brand-title text-5xl md:text-7xl font-extrabold tracking-tight mb-4 uppercase">
                Trillos Visual Records
            </h1>
            <p class="text-xl md:text-3xl text-gray-300 font-light mb-8 max-w-2xl mx-auto">
                Donde la Música Se Ve. | El Sonido del Mañana.
            </p>
            <a href="#music"
                class="inline-flex items-center bg-yellow-600 hover:bg-yellow-700 text-black font-semibold py-3 px-8 rounded-full transition-all duration-300 transform hover:scale-105 shadow-lg shadow-yellow-800/50">
                <i data-lucide="headphones" class="w-5 h-5 mr-3"></i>
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

            <!-- Search Bar -->
            <div class="max-w-md mx-auto mb-8 relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <i data-lucide="search" class="w-5 h-5 text-gray-500"></i>
                </div>
                <input type="text" id="song-search" 
                    class="block w-full pl-10 pr-3 py-2 border border-gray-700 rounded-full leading-5 bg-gray-900 text-gray-300 placeholder-gray-500 focus:outline-none focus:bg-black focus:border-yellow-500 focus:ring-1 focus:ring-yellow-500 sm:text-sm transition-colors duration-200" 
                    placeholder="Buscar canción o artista...">
            </div>

            <!-- Grid de Carátulas Reproducibles -->
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
                
                <?php if (count($songs) > 0): ?>
                    <?php foreach ($songs as $song): ?>
                    <!-- Tarjeta Dinámica -->
                    <div class="album-card group cursor-pointer" 
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
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8 justify-items-center max-w-6xl mx-auto">

                <?php if (count($videos) > 0): ?>
                    <?php foreach ($videos as $video): ?>
                    <!-- Video Dinámico -->
                    <div class="video-card cursor-pointer group" onclick="openVideoModal('<?php echo htmlspecialchars($video['video_path']); ?>')">
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

            <!-- Botones de Compartir (REQUISITO) -->
            <div class="flex justify-center space-x-4 mb-8">
                <button class="share-button p-3 rounded-full bg-gray-800 text-yellow-500 flex items-center"
                    onclick="sharePage('facebook')">
                    <i data-lucide="facebook" class="w-6 h-6"></i>
                </button>
                <button class="share-button p-3 rounded-full bg-gray-800 text-yellow-500 flex items-center"
                    onclick="sharePage('twitter')">
                    <i data-lucide="twitter" class="w-6 h-6"></i>
                </button>
                <button class="share-button p-3 rounded-full bg-gray-800 text-yellow-500 flex items-center"
                    onclick="sharePage('whatsapp')">
                    <i data-lucide="message-square" class="w-6 h-6"></i>
                </button>
                <button class="share-button p-3 rounded-full bg-yellow-500 text-black flex items-center"
                    onclick="copyLink()">
                    <i data-lucide="link" class="w-6 h-6"></i>
                </button>
            </div>

            <p class="text-sm text-gray-500">Para consultas: <a href="mailto:info@trillosvisualrecords.com"
                    class="text-yellow-600 hover:text-yellow-400 transition-colors">info@trillosvisualrecords.com</a>
            </p>
        </section>

    </main>

    <!-- REPRODUCTOR DE AUDIO GLOBAL FIJO (REQUISITO: Audios Reproducibles) -->
    <!-- REPRODUCTOR DE AUDIO GLOBAL FIJO (REQUISITO: Audios Reproducibles) -->
    <div id="audio-player-bar" class="p-4 flex flex-col md:flex-row items-center justify-between gap-4">
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
    </div>

    <!-- VENTANA MODAL PARA VIDEOS (REQUISITO: 1 sola ventana) -->
    <div id="video-modal"
        class="fixed inset-0 bg-black bg-opacity-90 hidden flex items-center justify-center z-[100] transition-opacity duration-300 opacity-0"
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
        class="fixed inset-0 premium-modal-backdrop hidden flex items-center justify-center z-[200] transition-opacity duration-300 opacity-0">
        <div class="premium-modal-content p-8 rounded-2xl max-w-sm w-full relative overflow-hidden text-center">
             <!-- Decoración de fondo -->
             <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-transparent via-yellow-500 to-transparent"></div>
             
             <div class="mb-5 inline-flex p-4 rounded-full bg-yellow-900/20 text-yellow-500 mb-4 ring-1 ring-yellow-500/50">
                <i data-lucide="lock" class="w-10 h-10"></i>
             </div>
             
            <h3 class="text-2xl font-bold text-yellow-500 mb-2 font-display">Acceso Requerido</h3>
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
    
    <!-- ELEMENTO CURSOR FALSO (ELIMINADO) -->

    <audio id="global-audio" preload="auto"></audio>

    <script>
        // Inicializa los iconos de Lucide
        lucide.createIcons();

        // Estado de inicio de sesión desde PHP
        const isLoggedIn = <?php echo isset($_SESSION['user_id']) ? 'true' : 'false'; ?>;

        // -------------------------
        // VARIABLES GLOBALES DE AUDIO
        // -------------------------
        const audio = document.getElementById('global-audio');
        const playPauseBtn = document.getElementById('play-pause-btn');
        const playPauseIcon = document.getElementById('play-pause-icon');
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

        // Función para mostrar mensajes personalizados (reemplaza alert())
        function showMessage(text) {
            const modalText = document.getElementById('modal-text');
            modalText.innerHTML = text; // Permitir HTML para formato
            
            const modal = document.getElementById('message-modal');
            modal.classList.remove('hidden');
            setTimeout(() => modal.classList.add('opacity-100'), 10);
        }

        function closeMessageModal() {
            const modal = document.getElementById('message-modal');
            modal.classList.remove('opacity-100');
            setTimeout(() => modal.classList.add('hidden'), 300);
        }

        // Formatea el tiempo de segundos a M:SS
        function formatTime(seconds) {
            const minutes = Math.floor(seconds / 60);
            const secs = Math.floor(seconds % 60);
            return `${minutes}:${secs < 10 ? '0' : ''}${secs}`;
        }

        // -------------------------
        // CONTROL DE REPRODUCTOR
        // -------------------------

        // Inicializa o reproduce una nueva pista
        function loadAndPlayTrack(card) {
            if (!isLoggedIn) {
                showMessage('Para disfrutar de la música de alta calidad de <strong>Trillos Records</strong>, por favor inicia sesión.');
                return;
            }

            const title = card.dataset.title;
            const artist = card.dataset.artist;
            const audioUrl = card.dataset.audioUrl;
            const cover = card.dataset.cover;

            if (currentTrack === audioUrl) {
                // Si es la misma pista, solo alternar play/pause
                togglePlayPause();
                return;
            }

            // Actualizar la interfaz del reproductor
            playerTitle.textContent = title;
            playerArtist.textContent = artist;
            playerCover.src = cover;
            audio.src = audioUrl;

            currentTrack = audioUrl;

            currentTrack = audioUrl;

            // Reemplaza el ícono de play con pause y reproduce
            updatePlayPauseIcon('pause');
            
            audio.play().catch(e => {
                showMessage(`Error al reproducir: ${e.message}. Asegúrate de que el audio se inicie con una interacción del usuario.`);
                // Reset UI if playback fails
                updatePlayPauseIcon('play');
            });
            isPlaying = true;
        }

        function updatePlayPauseIcon(iconName) {
            playPauseBtn.innerHTML = `<i data-lucide="${iconName}" class="w-6 h-6 fill-current"></i>`;
            lucide.createIcons();
        }

        function togglePlayPause() {
            if (!isLoggedIn) {
                showMessage('Por favor inicia sesión para usar el control de reproducción.');
                return;
            }

            if (currentTrack === null) {
                // Si no hay pista cargada, intenta cargar la primera como sugerencia
                const firstCard = albumCards[0];
                if (firstCard) {
                    loadAndPlayTrack(firstCard);
                    showMessage('Reproduciendo el primer sencillo. ¡Disfruta!');
                } else {
                    showMessage('No hay canciones disponibles para reproducir.');
                }
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

        function getCurrentTrackIndex() {
            if (!currentTrack) return -1;
            const cardsArray = Array.from(albumCards);
            return cardsArray.findIndex(card => card.dataset.audioUrl === currentTrack);
        }

        function playNextTrack() {
            if (!isLoggedIn) { showMessage('Inicia sesión para cambiar de pista.'); return; }
            if (albumCards.length === 0) return;
            
            let index = getCurrentTrackIndex();
            let nextIndex = index + 1;
            
            if (nextIndex >= albumCards.length) {
                nextIndex = 0; // Loop back to start
            }
            
            loadAndPlayTrack(albumCards[nextIndex]);
        }

        function playPrevTrack() {
            if (!isLoggedIn) { showMessage('Inicia sesión para cambiar de pista.'); return; }
            if (albumCards.length === 0) return;
            
            let index = getCurrentTrackIndex();
            let prevIndex = index - 1;
            
            if (prevIndex < 0) {
                prevIndex = albumCards.length - 1; // Loop to end
            }
            
            loadAndPlayTrack(albumCards[prevIndex]);
        }

        // Escucha clics en las carátulas para reproducir
        albumCards.forEach(card => {
            card.addEventListener('click', () => {
                loadAndPlayTrack(card);
            });
        });

        // Escucha el botón principal de play/pause
        playPauseBtn.addEventListener('click', togglePlayPause);
        
        // Escucha botones de anterior / siguiente
        document.getElementById('prev-btn').addEventListener('click', playPrevTrack);
        document.getElementById('next-btn').addEventListener('click', playNextTrack);

        // -------------------------
        // SEARCH FUNCTIONALITY
        // -------------------------
        const searchInput = document.getElementById('song-search');
        
        if (searchInput) {
            searchInput.addEventListener('input', (e) => {
                const searchTerm = e.target.value.toLowerCase();
                
                albumCards.forEach(card => {
                    const title = card.dataset.title.toLowerCase();
                    const artist = card.dataset.artist.toLowerCase();
                    
                    if (title.includes(searchTerm) || artist.includes(searchTerm)) {
                        card.style.display = 'block';
                    } else {
                        card.style.display = 'none';
                    }
                });
            });
        }

        // Evento que se dispara al cargar metadata del audio
        audio.addEventListener('loadedmetadata', () => {
            totalTimeEl.textContent = formatTime(audio.duration);
            seekBar.max = audio.duration;
        });

        // Evento que se dispara durante la reproducción (actualiza la barra)
        const waveformActive = document.getElementById('waveform-active');

        audio.addEventListener('timeupdate', () => {
            currentTimeEl.textContent = formatTime(audio.currentTime);
            seekBar.value = audio.currentTime;

            // Actualizar el ancho visual de la onda
            const progressPercent = (audio.currentTime / audio.duration) * 100;
            waveformActive.style.width = `${progressPercent}%`;
        });

        // Evento para arrastrar la barra de búsqueda
        seekBar.addEventListener('input', () => {
            audio.currentTime = seekBar.value;
            const progressPercent = (audio.currentTime / audio.duration) * 100;
            waveformActive.style.width = `${progressPercent}%`;
        });

        // Evento para cambiar el volumen
        volumeBar.addEventListener('input', () => {
            audio.volume = volumeBar.value / 100;
        });

        // -------------------------
        // CONTROL DE VIDEOS
        // -------------------------

        const videoModal = document.getElementById('video-modal');
        const videoIframeContainer = document.getElementById('video-iframe-container');

        // Abre el modal e inserta el video de YouTube
        function openVideoModal(youtubeId) {
            if (!isLoggedIn) {
                showMessage('Debes iniciar sesión o registrarte para ver videos.');
                return;
            }

            // Pausar audio al abrir el video
            if (isPlaying) {
                togglePlayPause();
            }

            const iframe = document.createElement('iframe');
            iframe.setAttribute('src', `https://www.youtube-nocookie.com/embed/${youtubeId}?autoplay=1&rel=0`);
            iframe.setAttribute('frameborder', '0');
            iframe.setAttribute('allow', 'accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture');
            iframe.setAttribute('allowfullscreen', '');
            iframe.className = 'w-full h-full';

            videoIframeContainer.innerHTML = ''; // Limpiar contenido anterior
            videoIframeContainer.appendChild(iframe);

            // Mostrar modal con transición
            videoModal.classList.remove('hidden');
            setTimeout(() => videoModal.classList.add('opacity-100'), 10);
        }

        // Cierra el modal y detiene la reproducción del video
        function closeVideoModal() {
            // Ocultar modal con transición
            videoModal.classList.remove('opacity-100');
            setTimeout(() => {
                videoModal.classList.add('hidden');
                // Detener el video quitando el iframe
                videoIframeContainer.innerHTML = '';
            }, 300);
        }

        // -------------------------
        // FUNCIONES DE COMPARTIR
        // -------------------------

        // Función para compartir en redes sociales (simulado)
        function sharePage(platform) {
            const url = encodeURIComponent(window.location.href);
            const title = encodeURIComponent(document.title);
            let shareUrl = '';

            switch (platform) {
                case 'facebook':
                    shareUrl = `https://www.facebook.com/sharer/sharer.php?u=${url}`;
                    break;
                case 'twitter':
                    shareUrl = `https://twitter.com/intent/tweet?url=${url}&text=${title}`;
                    break;
                case 'whatsapp':
                    // Usa window.location.origin ya que window.location.href no funciona bien en iframe
                    shareUrl = `whatsapp://send?text=${title}: ${window.location.origin}`;
                    break;
                default:
                    return;
            }

            // Abrir la ventana de compartir
            window.open(shareUrl, '_blank', 'width=600,height=400');
        }

        function copyLink() {
            navigator.clipboard.writeText(window.location.href).then(() => {
                showMessage('¡Enlace copiado al portapapeles!');
            }).catch(err => {
                console.error('Error al copiar: ', err);
            });
        }

        // -------------------------
        // PROFILE DROPDOWN
        // -------------------------
        function toggleProfileMenu() {
            const dropdown = document.getElementById('profile-dropdown');
            dropdown.classList.toggle('hidden');
        }

        // Close dropdown when clicking outside
        window.addEventListener('click', function(e) {
            const dropdown = document.getElementById('profile-dropdown');
            const container = document.querySelector('.profile-menu-container');
            
            if (dropdown && !dropdown.classList.contains('hidden')) {
            if (!currentTrack) return -1;
            const cardsArray = Array.from(albumCards);
            return cardsArray.findIndex(card => card.dataset.audioUrl === currentTrack);
        }

        function playNextTrack() {
            if (!isLoggedIn) { showMessage('Inicia sesión para cambiar de pista.'); return; }
            if (albumCards.length === 0) return;
            
            let index = getCurrentTrackIndex();
            let nextIndex = index + 1;
            
            if (nextIndex >= albumCards.length) {
                nextIndex = 0; // Loop back to start
            }
            
            loadAndPlayTrack(albumCards[nextIndex]);
        }

        function playPrevTrack() {
            if (!isLoggedIn) { showMessage('Inicia sesión para cambiar de pista.'); return; }
            if (albumCards.length === 0) return;
            
            let index = getCurrentTrackIndex();
            let prevIndex = index - 1;
            
            if (prevIndex < 0) {
                prevIndex = albumCards.length - 1; // Loop to end
            }
            
            loadAndPlayTrack(albumCards[prevIndex]);
        }

        // Escucha clics en las carátulas para reproducir
        albumCards.forEach(card => {
            card.addEventListener('click', () => {
                loadAndPlayTrack(card);
            });
        });

        // Escucha el botón principal de play/pause
        playPauseBtn.addEventListener('click', togglePlayPause);
        
        // Escucha botones de anterior / siguiente
        document.getElementById('prev-btn').addEventListener('click', playPrevTrack);
        document.getElementById('next-btn').addEventListener('click', playNextTrack);

        // -------------------------
        // SEARCH FUNCTIONALITY
        // -------------------------
        const searchInput = document.getElementById('song-search');
        
        if (searchInput) {
            searchInput.addEventListener('input', (e) => {
                const searchTerm = e.target.value.toLowerCase();
                
                albumCards.forEach(card => {
                    const title = card.dataset.title.toLowerCase();
                    const artist = card.dataset.artist.toLowerCase();
                    
                    if (title.includes(searchTerm) || artist.includes(searchTerm)) {
                        card.style.display = 'block';
                    } else {
                        card.style.display = 'none';
                    }
                });
            });
        }

        // Evento que se dispara al cargar metadata del audio
        audio.addEventListener('loadedmetadata', () => {
            totalTimeEl.textContent = formatTime(audio.duration);
            seekBar.max = audio.duration;
        });

        // Evento que se dispara durante la reproducción (actualiza la barra)
        const waveformActive = document.getElementById('waveform-active');

        audio.addEventListener('timeupdate', () => {
            currentTimeEl.textContent = formatTime(audio.currentTime);
            seekBar.value = audio.currentTime;

            // Actualizar el ancho visual de la onda
            const progressPercent = (audio.currentTime / audio.duration) * 100;
            waveformActive.style.width = `${progressPercent}%`;
        });

        // Evento para arrastrar la barra de búsqueda
        seekBar.addEventListener('input', () => {
            audio.currentTime = seekBar.value;
            const progressPercent = (audio.currentTime / audio.duration) * 100;
            waveformActive.style.width = `${progressPercent}%`;
        });

        // Evento para cambiar el volumen
        volumeBar.addEventListener('input', () => {
            audio.volume = volumeBar.value / 100;
        });

        // -------------------------
        // CONTROL DE VIDEOS
        // -------------------------

        // -------------------------
        // FUNCIONES DE COMPARTIR
        // -------------------------

        // Función para compartir en redes sociales (simulado)
        function sharePage(platform) {
            const url = encodeURIComponent(window.location.href);
            const title = encodeURIComponent(document.title);
            let shareUrl = '';

            switch (platform) {
                case 'facebook':
                    shareUrl = `https://www.facebook.com/sharer/sharer.php?u=${url}`;
                    break;
                case 'twitter':
                    shareUrl = `https://twitter.com/intent/tweet?url=${url}&text=${title}`;
                    break;
                case 'whatsapp':
                    // Usa window.location.origin ya que window.location.href no funciona bien en iframe
                    shareUrl = `whatsapp://send?text=${title}: ${window.location.origin}`;
                    break;
                default:
                    return;
            }

            // Abrir la ventana de compartir
            window.open(shareUrl, '_blank', 'width=600,height=400');
        }

        function copyLink() {
            navigator.clipboard.writeText(window.location.href).then(() => {
                showMessage('¡Enlace copiado al portapapeles!');
            }).catch(err => {
                console.error('Error al copiar: ', err);
            });
        }

        // -------------------------
        // PROFILE DROPDOWN
        // -------------------------
        function toggleProfileMenu() {
            const dropdown = document.getElementById('profile-dropdown');
            dropdown.classList.toggle('hidden');
        }

        // Close dropdown when clicking outside
        window.addEventListener('click', function(e) {
            const dropdown = document.getElementById('profile-dropdown');
            const container = document.querySelector('.profile-menu-container');
            
            if (dropdown && !dropdown.classList.contains('hidden')) {
                if (!container.contains(e.target)) {
                    dropdown.classList.add('hidden');
                }
            }
        });
        // Modal de Video (Local)
        function openVideoModal(videoPath) {
            const modal = document.getElementById('video-modal');
            const player = document.getElementById('video-player');
            const source = player.querySelector('source');
            
            source.src = videoPath;
            player.load();
            
            modal.classList.remove('hidden');
            setTimeout(() => {
                modal.classList.remove('opacity-0');
                 player.play().catch(e => console.log('Autoplay blocked'));
            }, 10);
        }

        function closeVideoModal() {
            const modal = document.getElementById('video-modal');
            const player = document.getElementById('video-player');
            
            player.pause();
            player.currentTime = 0;
            
            modal.classList.add('opacity-0');
            setTimeout(() => {
                modal.classList.add('hidden');
            }, 300);
        }
    </script>
</body>

</html>
