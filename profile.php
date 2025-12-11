<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Perfil | Trillos Visual Records</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Sortable/1.15.0/Sortable.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=Inter:wght@300;400;600&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #050505; color: #fff; }
        .brand-title { font-family: 'Playfair Display', serif; }
        .glass-panel { background: rgba(255, 255, 255, 0.03); backdrop-filter: blur(10px); border: 1px solid rgba(255, 255, 255, 0.05); }
        .tab-active { border-bottom: 2px solid #EAB308; color: #EAB308; }
        .tab-inactive { color: #9CA3AF; }
        .tab-inactive { color: #9CA3AF; }
        .tab-inactive:hover { color: #fff; }
        
        /* Estilos de alternar contraseña */
        .input-group { position: relative; }
        .password-toggle {
            position: absolute;
            right: 1rem;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: rgba(255, 255, 255, 0.5);
            z-index: 10;
            transition: color 0.3s ease;
        }
        .password-toggle:hover { color: var(--color-primary, #EAB308); }
        .input-with-icon { padding-right: 2.5rem; }
    </style>
</head>
<body class="min-h-screen pb-20">

    <!-- Barra de navegación simple -->
    <nav class="fixed top-0 w-full z-50 glass-panel border-b border-white/5 bg-black/80">
        <div class="max-w-7xl mx-auto px-4 py-4 flex justify-between items-center">
            <a href="index.php" class="group flex items-center gap-3 px-5 py-2.5 rounded-full bg-white/5 border border-white/10 hover:border-yellow-500/50 hover:bg-white/10 transition-all duration-300">
                <div class="w-6 h-6 rounded-full bg-yellow-500/10 flex items-center justify-center text-yellow-500 group-hover:bg-yellow-500 group-hover:text-black transition-colors">
                    <i data-lucide="arrow-left" class="w-3.5 h-3.5"></i>
                </div>
                <span class="font-medium text-sm text-gray-200 group-hover:text-white">Volver al Inicio</span>
            </a>
            
            <div class="flex items-center gap-4">
               <a href="logout.php" class="flex items-center gap-2 px-4 py-2 rounded-full text-gray-400 hover:text-red-400 hover:bg-red-500/10 transition-all text-sm font-medium">
                   <span>Cerrar Sesión</span>
                   <i data-lucide="log-out" class="w-4 h-4"></i>
               </a>
            </div>
        </div>
    </nav>

    <main class="max-w-4xl mx-auto pt-24 px-4">
        
        <!-- Encabezado del perfil -->
        <div class="glass-panel rounded-3xl p-8 mb-8 flex flex-col md:flex-row items-center gap-8 relative overflow-hidden">
            <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-transparent via-yellow-500/50 to-transparent"></div>
            
            <!-- Avatar -->
            <div class="relative group">
                <div class="w-32 h-32 rounded-full overflow-hidden border-4 border-black shadow-xl ring-2 ring-yellow-500/30">
                    <img id="profile-pic" src="https://ui-avatars.com/api/?name=User&background=random" alt="Profile" class="w-full h-full object-cover">
                </div>
                <button onclick="triggerPhotoUpload()" class="absolute bottom-0 right-0 p-2 bg-yellow-500 rounded-full text-black hover:bg-yellow-400 transition shadow-lg">
                    <i data-lucide="camera" class="w-5 h-5"></i>
                </button>
                <input type="file" id="photo-input" class="hidden" accept="image/*" onchange="uploadPhoto()">
            </div>

            <!-- Información -->
            <div class="flex-1 text-center md:text-left">
                <div class="flex items-center justify-center md:justify-start gap-3 mb-2">
                    <h1 id="user-name" class="text-3xl font-bold text-white brand-title">Cargando...</h1>
                    <button onclick="openEditNameModal()" class="text-gray-500 hover:text-yellow-500 transition">
                        <i data-lucide="edit-2" class="w-4 h-4"></i>
                    </button>
                </div>
                <p class="text-gray-400 mb-6">Miembro de Trillos Visual Records</p>
                
                <!-- Estadísticas -->
                <div class="flex justify-center md:justify-start gap-8">
                    <div class="text-center">
                        <span id="stat-playlists" class="block text-2xl font-bold text-yellow-500">0</span>
                        <span class="text-xs text-gray-500 uppercase tracking-wider">Listas</span>
                    </div>
                    <div class="text-center">
                        <span id="stat-likes" class="block text-2xl font-bold text-yellow-500">0</span>
                        <span class="text-xs text-gray-500 uppercase tracking-wider">Me Gusta</span>
                    </div>
                </div>
                
                <!-- Botón de cambiar contraseña -->
                <button onclick="openChangePasswordModal()" class="mt-4 flex items-center gap-2 px-4 py-2 bg-white/5 hover:bg-white/10 border border-white/10 hover:border-yellow-500/50 rounded-lg transition-all text-sm font-medium text-gray-300 hover:text-white mx-auto md:mx-0">
                    <i data-lucide="lock" class="w-4 h-4"></i>
                    <span>Cambiar Contraseña</span>
                </button>
            </div>
        </div>

        <!-- Pestañas de navegación -->
        <div class="flex border-b border-gray-800 mb-8 overflow-x-auto">
            <button onclick="switchTab('playlists')" id="tab-playlists" class="tab-active px-6 py-4 font-medium transition-colors whitespace-nowrap">
                Mis Listas
            </button>
            <button onclick="switchTab('likes')" id="tab-likes" class="tab-inactive px-6 py-4 font-medium transition-colors whitespace-nowrap">
                Me Gusta
            </button>
            <button onclick="switchTab('history')" id="tab-history" class="tab-inactive px-6 py-4 font-medium transition-colors whitespace-nowrap">
                Historial
            </button>
        </div>

        <!-- Secciones de contenido -->
        
        <!-- Sección de listas de reproducción -->
        <div id="section-playlists" class="space-y-6">
            <!-- Vista de lista de listas de reproducción -->
            <div id="playlists-list-view">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-xl font-bold text-white">Listas de Reproducción</h2>
                    <button onclick="openCreatePlaylistModal()" class="bg-yellow-500/10 hover:bg-yellow-500 text-yellow-500 hover:text-black px-4 py-2 rounded-lg transition flex items-center gap-2 text-sm font-medium">
                        <i data-lucide="plus" class="w-4 h-4"></i> Nueva Lista
                    </button>
                </div>
                
                <div id="playlists-grid" class="grid grid-cols-2 md:grid-cols-3 gap-4">
                    <!-- Contenido dinámico -->
                </div>
            </div>

            <!-- Vista de detalle de lista de reproducción -->
            <div id="playlist-detail-view" class="hidden group">
                 <div class="flex items-center gap-4 mb-6">
                    <button onclick="closePlaylistDetail()" class="p-2 rounded-full hover:bg-white/10 text-gray-400 hover:text-white transition">
                        <i data-lucide="arrow-left" class="w-6 h-6"></i>
                    </button>
                    
                    <div class="flex items-center gap-4">
                        <div class="flex items-baseline gap-3">
                             <h2 id="detail-playlist-name" class="text-2xl font-bold text-white">Playlist Name</h2>
                             <!-- Acciones en línea -->
                            <div class="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                <button onclick="openRenamePlaylistModal()" class="p-1.5 rounded-full hover:bg-white/10 text-gray-400 hover:text-white transition" title="Editar Nombre">
                                    <i data-lucide="edit-2" class="w-4 h-4"></i>
                                </button>
                                <button onclick="confirmDeletePlaylist()" class="p-1.5 rounded-full hover:bg-red-500/20 text-gray-400 hover:text-red-500 transition" title="Eliminar Lista">
                                    <i data-lucide="trash-2" class="w-4 h-4"></i>
                                </button>
                            </div>
                        </div>
                        <span id="detail-playlist-count" class="text-sm text-gray-500">0 canciones</span>
                    </div>
                    
                    <button onclick="openAddSongModal()" class="ml-auto bg-white text-black hover:bg-gray-200 px-4 py-2 rounded-full font-medium flex items-center gap-2 transition">
                        <i data-lucide="plus" class="w-4 h-4"></i> Añadir Canciones
                    </button>
                </div>

                <div id="playlist-items-list" class="space-y-2">
                    <!-- Elementos dinámicos -->
                </div>
            </div>
        </div>

        <!-- Sección de Me Gusta -->
        <div id="section-likes" class="hidden space-y-8">
            <!-- Canciones que te gustan -->
            <div>
                <h3 class="text-lg font-bold text-gray-300 mb-4 flex items-center gap-2">
                    <i data-lucide="music" class="w-5 h-5 text-yellow-500"></i> Canciones
                </h3>
                <div id="liked-songs-grid" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- Dynamic -->
                </div>
            </div>
            <!-- Videos que te gustan -->
            <div>
                <h3 class="text-lg font-bold text-gray-300 mb-4 flex items-center gap-2">
                    <i data-lucide="video" class="w-5 h-5 text-yellow-500"></i> Videos
                </h3>
                <div id="liked-videos-grid" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- Dynamic -->
                </div>
            </div>
        </div>

        <!-- Sección de historial -->
        <div id="section-history" class="hidden space-y-6">
            <h2 class="text-xl font-bold text-white">Actividad Reciente</h2>
            <div id="history-list" class="space-y-2">
                <!-- Dynamic -->
            </div>
        </div>

    </main>

    <!-- Plantilla de modal genérico -->
    <div id="generic-modal" class="fixed inset-0 bg-black/80 hidden z-[60] flex items-center justify-center opacity-0 transition-opacity duration-300">
        <div id="generic-modal-content" class="bg-[#111] border border-gray-800 rounded-2xl p-8 max-w-sm w-full transform scale-95 opacity-0 transition-all duration-300 relative">
            <button onclick="closeGenericModal()" class="absolute top-4 right-4 text-gray-400 hover:text-white">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
            <h3 id="generic-modal-title" class="text-xl font-bold text-white mb-6">Título</h3>
            <div id="generic-modal-body">
                <!-- Entrada dinámica -->
            </div>
            <div class="flex justify-end gap-3 mt-6">
                <button onclick="closeGenericModal()" class="px-4 py-2 text-gray-400 hover:text-white text-sm">Cancelar</button>
                <button id="generic-modal-action" class="px-4 py-2 bg-yellow-500 text-black font-medium rounded-lg hover:bg-yellow-400">Guardar</button>
            </div>
        </div>
    </div>

    <!-- Modal de diálogo de confirmación -->
    <div id="confirm-modal" class="fixed inset-0 bg-black/80 hidden z-[80] flex items-center justify-center opacity-0 transition-opacity duration-300">
        <div class="bg-[#111] border border-gray-800 rounded-2xl p-6 max-w-sm w-full transform scale-95 opacity-0 transition-all duration-300 relative" id="confirm-modal-content">
            <h3 class="text-xl font-bold text-white mb-2">¿Estás seguro?</h3>
            <p class="text-gray-400 mb-6 text-sm">Esta acción no se puede deshacer.</p>
            <div class="flex justify-end gap-3">
                <button onclick="closeConfirmModal()" class="px-4 py-2 text-gray-400 hover:text-white text-sm">Cancelar</button>
                <button id="confirm-modal-action" class="px-4 py-2 bg-red-500 text-white font-medium rounded-lg hover:bg-red-600 transition">Eliminar</button>
            </div>
        </div>
    </div>

    <!-- Modal de selector de canciones -->
    <div id="song-selector-modal" class="fixed inset-0 bg-black/80 hidden z-[70] flex items-center justify-center opacity-0 transition-opacity duration-300">
        <div class="bg-[#111] border border-gray-800 rounded-2xl p-6 max-w-lg w-full h-[80vh] flex flex-col transform scale-95 opacity-0 transition-all duration-300 relative" id="song-selector-content">
            <div class="flex justify-between items-center mb-6">
                <h3 class="text-xl font-bold text-white">Añadir a la lista</h3>
                <button onclick="closeSongSelector()" class="text-gray-400 hover:text-white">
                    <i data-lucide="x" class="w-6 h-6"></i>
                </button>
            </div>
            
            <input type="text" id="song-search-input" placeholder="Buscar canciones..." class="w-full bg-black/50 border border-gray-700 rounded-lg px-4 py-3 text-white mb-4 focus:border-yellow-500 focus:outline-none">
            
            <div id="song-selector-list" class="flex-1 overflow-y-auto space-y-2 pr-2 custom-scrollbar">
                <!-- Lista de canciones -->
            </div>
        </div>
    </div>

    <!-- Modal de cambiar contraseña -->
    <div id="password-modal" class="fixed inset-0 bg-black/80 hidden z-[60] flex items-center justify-center opacity-0 transition-opacity duration-300">
        <div id="password-modal-content" class="bg-[#111] border border-gray-800 rounded-2xl p-8 max-w-md w-full transform scale-95 opacity-0 transition-all duration-300 relative">
            <button onclick="closePasswordModal()" class="absolute top-4 right-4 text-gray-400 hover:text-white">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
            <h3 class="text-xl font-bold text-white mb-6">Cambiar Contraseña</h3>
            
            <div class="space-y-4">
                <div>
                    <label class="block text-sm text-gray-400 mb-2">Contraseña Actual</label>
                    <div class="input-group">
                        <input type="password" id="current-password" class="w-full bg-black/50 border border-gray-700 rounded-lg px-4 py-3 text-white focus:border-yellow-500 focus:outline-none transition input-with-icon" placeholder="••••••••">
                        <i data-lucide="eye" class="password-toggle w-5 h-5" onclick="togglePassword('current-password', this)"></i>
                    </div>
                </div>
                
                <div>
                    <label class="block text-sm text-gray-400 mb-2">Nueva Contraseña</label>
                    <div class="input-group">
                        <input type="password" id="new-password" class="w-full bg-black/50 border border-gray-700 rounded-lg px-4 py-3 text-white focus:border-yellow-500 focus:outline-none transition input-with-icon" placeholder="••••••••">
                        <i data-lucide="eye" class="password-toggle w-5 h-5" onclick="togglePassword('new-password', this)"></i>
                    </div>
                </div>
                
                <!-- Requisitos de contraseña -->
                <div class="text-xs text-gray-500 space-y-1 pl-1">
                    <div class="flex items-center space-x-2 req-item" id="pwd-req-lower">
                        <div class="w-1.5 h-1.5 rounded-full bg-gray-600 transition-colors"></div>
                        <span>Minúscula</span>
                    </div>
                    <div class="flex items-center space-x-2 req-item" id="pwd-req-upper">
                        <div class="w-1.5 h-1.5 rounded-full bg-gray-600 transition-colors"></div>
                        <span>Mayúscula</span>
                    </div>
                    <div class="flex items-center space-x-2 req-item" id="pwd-req-special">
                        <div class="w-1.5 h-1.5 rounded-full bg-gray-600 transition-colors"></div>
                        <span>Carácter especial</span>
                    </div>
                </div>
                
                <div>
                    <label class="block text-sm text-gray-400 mb-2">Confirmar Nueva Contraseña</label>
                    <div class="input-group">
                        <input type="password" id="confirm-password" class="w-full bg-black/50 border border-gray-700 rounded-lg px-4 py-3 text-white focus:border-yellow-500 focus:outline-none transition input-with-icon" placeholder="••••••••">
                        <i data-lucide="eye" class="password-toggle w-5 h-5" onclick="togglePassword('confirm-password', this)"></i>
                    </div>
                </div>
            </div>
            
            <div class="flex justify-end gap-3 mt-6">
                <button onclick="closePasswordModal()" class="px-4 py-2 text-gray-400 hover:text-white text-sm">Cancelar</button>
                <button onclick="changePassword()" class="px-6 py-2 bg-yellow-500 text-black font-medium rounded-lg hover:bg-yellow-400 transition">Guardar</button>
            </div>
        </div>
    </div>


    <!-- Notificación Toast -->
    <div id="toast-container" class="fixed bottom-10 left-1/2 transform -translate-x-1/2 z-[4000] pointer-events-none transition-all duration-300 opacity-0 translate-y-4">
        <div class="bg-black/80 backdrop-blur-xl border border-green-500/30 rounded-full px-6 py-3 shadow-2xl flex items-center gap-3">
             <div class="w-8 h-8 rounded-full bg-green-500/20 flex items-center justify-center text-green-500 relative">
                 <i data-lucide="check" class="w-5 h-5 absolute z-10"></i>
                 <div class="absolute w-full h-full bg-green-500/30 rounded-full animate-ping opacity-75"></div>
             </div>
             <div>
                 <p class="text-white font-bold text-sm" id="toast-message">Canción Añadida</p>
             </div>
        </div>
    </div>

    <!-- Scripts -->
    <script>
        lucide.createIcons();

        function showToast(message) {
            const toast = document.getElementById('toast-container');
            const msgEl = document.getElementById('toast-message');
            if(toast && msgEl) {
                msgEl.textContent = message;
                toast.classList.remove('translate-y-4', 'opacity-0');
                
                // Animación de entrada
                lucide.createIcons();
                
                setTimeout(() => {
                    toast.classList.add('translate-y-4', 'opacity-0');
                }, 3000);
            }
        }

        function togglePassword(inputId, toggleIcon) {
            const input = document.getElementById(inputId);
            if (input.type === 'password') {
                input.type = 'text';
                toggleIcon.setAttribute('data-lucide', 'eye-off');
            } else {
                input.type = 'password';
                toggleIcon.setAttribute('data-lucide', 'eye');
            }
            lucide.createIcons();
        }


        // Cargar datos iniciales
        document.addEventListener('DOMContentLoaded', () => {
            loadStats();
            loadPlaylists(); 
        });

        // ------------------------------------------
        // LÓGICA DE PESTAÑAS
        // ------------------------------------------
        function switchTab(tabName) {
            // Actualizar estilos de pestaña
            ['playlists', 'likes', 'history'].forEach(t => {
                const btn = document.getElementById(`tab-${t}`);
                const section = document.getElementById(`section-${t}`);
                
                if (t === tabName) {
                    btn.classList.remove('tab-inactive');
                    btn.classList.add('tab-active');
                    section.classList.remove('hidden');
                    
                    if (t === 'likes') loadLikes();
                    if (t === 'history') loadHistory();
                    if (t === 'playlists') loadPlaylists();
                } else {
                    btn.classList.add('tab-inactive');
                    btn.classList.remove('tab-active');
                    section.classList.add('hidden');
                }
            });
        }

        // ------------------------------------------
        // LLAMADAS A API
        // ------------------------------------------
        async function api(formData) {
            try {
                const response = await fetch('api_profile.php', {
                    method: 'POST',
                    body: formData
                });
                return await response.json();
            } catch (error) {
                console.error('Error:', error);
                alert('Ocurrió un error de conexión');
                return { success: false };
            }
        }

        function loadStats() {
            const fd = new FormData();
            fd.append('action', 'get_stats');
            
            api(fd).then(data => {
                if (data.success) {
                    window.isGoogleUser = !!data.user.google_id; // Almacenar bandera
                    document.getElementById('user-name').textContent = data.user.full_name;
                    const img = document.getElementById('profile-pic');
                    if (data.user.profile_pic) {
                        img.src = data.user.profile_pic;
                    } else {
                        // Usar nombre de usuario para iniciales con colores de marca (Fondo dorado, Texto negro)
                        img.src = `https://ui-avatars.com/api/?name=${encodeURIComponent(data.user.full_name)}&background=FFD700&color=000000`; 
                    }
                    document.getElementById('stat-playlists').textContent = data.stats.playlists;
                    document.getElementById('stat-likes').textContent = data.stats.likes;
                }
            });
        }

        function loadPlaylists() {
            const fd = new FormData();
            fd.append('action', 'get_playlists');
            
            api(fd).then(data => {
                const grid = document.getElementById('playlists-grid');
                grid.innerHTML = '';
                
                if (data.success && data.playlists.length > 0) {
                    data.playlists.forEach(pl => {
                        const el = document.createElement('div');
                        el.className = 'glass-panel p-6 rounded-xl hover:bg-white/5 transition group cursor-pointer';
                        el.onclick = () => openPlaylistDetail(pl.id); // Añadir manejador de clics
                        el.innerHTML = `
                            <div class="flex items-center gap-4 mb-4">
                                <div class="w-12 h-12 bg-gradient-to-br from-yellow-500 to-yellow-700 rounded-lg shadow-lg flex items-center justify-center">
                                    <i data-lucide="music" class="w-6 h-6 text-black"></i>
                                </div>
                                <div>
                                    <h3 class="font-bold text-white group-hover:text-yellow-500 transition">${pl.name}</h3>
                                    <p class="text-xs text-gray-500">Creada el ${new Date(pl.created_at).toLocaleDateString()}</p>
                                </div>
                            </div>
                        `;
                        grid.appendChild(el);
                    });
                } else {
                    grid.innerHTML = '<p class="col-span-full text-center text-gray-500 py-10">No tienes listas de reproducción. ¡Crea una!</p>';
                }
                lucide.createIcons();
            });
        }

        function loadLikes() {
            const fd = new FormData();
            fd.append('action', 'get_likes');
            
            api(fd).then(data => {
                const songsGrid = document.getElementById('liked-songs-grid');
                songsGrid.innerHTML = '';
                if(data.songs.length > 0) {
                    data.songs.forEach(song => {
                        songsGrid.innerHTML += `
                            <div class="flex items-center gap-3 p-3 glass-panel rounded-lg hover:bg-white/10 transition">
                                <img src="${song.cover_path}" class="w-12 h-12 rounded bg-gray-800 object-cover">
                                <div class="flex-1 min-w-0">
                                    <p class="font-medium text-white truncate">${song.title}</p>
                                    <p class="text-xs text-gray-400 truncate">${song.artist}</p>
                                </div>
                                <button onclick="window.location.href='index.php?song=${song.id}'" class="p-2 rounded-full bg-yellow-500/10 text-yellow-500 hover:bg-yellow-500 hover:text-black transition">
                                    <i data-lucide="play" class="w-4 h-4"></i>
                                </button>
                            </div>
                        `;
                    });
                } else {
                    songsGrid.innerHTML = '<p class="text-gray-500 text-sm italic">No hay canciones que te gusten.</p>';
                }

                const videosGrid = document.getElementById('liked-videos-grid');
                videosGrid.innerHTML = '';
                if(data.videos.length > 0) {
                    data.videos.forEach(video => {
                        videosGrid.innerHTML += `
                            <div class="flex items-center gap-3 p-3 glass-panel rounded-lg hover:bg-white/10 transition">
                                <img src="${video.cover_path}" class="w-16 aspect-video rounded bg-gray-800 object-cover">
                                <div class="flex-1 min-w-0">
                                    <p class="font-medium text-white truncate">${video.title}</p>
                                </div>
                                <button onclick="window.location.href='index.php?video=${video.id}'" class="p-2 rounded-full bg-yellow-500/10 text-yellow-500 hover:bg-yellow-500 hover:text-black transition">
                                    <i data-lucide="play" class="w-4 h-4"></i>
                                </button>
                            </div>
                        `;
                    });
                } else {
                    videosGrid.innerHTML = '<p class="text-gray-500 text-sm italic">No hay videos que te gusten.</p>';
                }
                lucide.createIcons();
            });
        }

        function loadHistory() {
            const fd = new FormData();
            fd.append('action', 'get_history');
            
            api(fd).then(data => {
                const list = document.getElementById('history-list');
                list.innerHTML = '';
                
                if (data.history.length > 0) {
                    data.history.forEach(item => {
                        const isSong = item.item_type === 'song';
                        const icon = isSong ? 'music' : 'video';
                        const title = item.title;
                        
                        list.innerHTML += `
                            <div class="flex items-center gap-4 p-4 border-b border-white/5 hover:bg-white/5 transition rounded-lg">
                                <div class="w-10 h-10 rounded-full bg-gray-800 flex items-center justify-center text-gray-400">
                                    <i data-lucide="${icon}" class="w-5 h-5"></i>
                                </div>
                                <div class="flex-1">
                                    <p class="text-white font-medium">${title}</p>
                                    <p class="text-xs text-gray-500">Reproducido el ${new Date(item.played_at).toLocaleString()}</p>
                                </div>
                            </div>
                        `;
                    });
                } else {
                    list.innerHTML = '<p class="text-center text-gray-500 py-10">Tu historial está vacío.</p>';
                }
                lucide.createIcons();
            });
        }

        // ------------------------------------------
        // LÓGICA DE MODALES
        // ------------------------------------------
        const modal = document.getElementById('generic-modal');
        const modalContent = document.getElementById('generic-modal-content');
        const modalTitle = document.getElementById('generic-modal-title');
        const modalBody = document.getElementById('generic-modal-body');
        const modalAction = document.getElementById('generic-modal-action');

        function openModal(title, inputPlaceholder, initialValue = '', onConfirm) {
            modalTitle.textContent = title;
            modalBody.innerHTML = `
                <input type="text" id="modal-input" class="w-full bg-black/50 border border-gray-700 rounded-lg px-4 py-3 text-white focus:border-yellow-500 focus:outline-none transition" placeholder="${inputPlaceholder}" value="${initialValue}">
            `;
            
            // Enfocar entrada después de abrir modal
            setTimeout(() => document.getElementById('modal-input').focus(), 100);

            modalAction.onclick = () => {
                const val = document.getElementById('modal-input').value;
                if(val) onConfirm(val);
                closeGenericModal();
            };

            // Soporte para tecla Enter
            modalBody.onkeyup = (e) => {
                if(e.key === 'Enter') modalAction.click();
            };

            modal.classList.remove('hidden');
            setTimeout(() => {
                modal.classList.remove('opacity-0');
                modalContent.classList.remove('scale-95', 'opacity-0');
                modalContent.classList.add('scale-100', 'opacity-100');
            }, 10);
        }

        function closeGenericModal() {
            modal.classList.add('opacity-0');
            modalContent.classList.remove('scale-100', 'opacity-100');
            modalContent.classList.add('scale-95', 'opacity-0');
            setTimeout(() => modal.classList.add('hidden'), 300);
        }

        // ------------------------------------------
        // ACCIONES (USANDO MODALES)
        // ------------------------------------------
        function openCreatePlaylistModal() {
            openModal('Nueva Lista', 'Nombre de la lista', '', async (name) => {
                const fd = new FormData();
                fd.append('action', 'create_playlist');
                fd.append('name', name);
                
                const res = await api(fd);
                if (res.success) {
                    loadPlaylists();
                    loadStats();
                } else {
                    alert(res.message);
                }
            });
        }

        function openEditNameModal() {
            const current = document.getElementById('user-name').textContent;
            openModal('Editar Nombre', 'Tu nombre', current, async (newName) => {
                if (newName !== current) {
                    const fd = new FormData();
                    fd.append('action', 'update_profile');
                    fd.append('full_name', newName);
                    
                    const res = await api(fd);
                    if (res.success) {
                        loadStats();
                    } else {
                        alert(res.message);
                    }
                }
            });
        }

        function triggerPhotoUpload() {
            document.getElementById('photo-input').click();
        }

        async function uploadPhoto() {
            const input = document.getElementById('photo-input');
            if (input.files && input.files[0]) {
                const fd = new FormData();
                fd.append('action', 'update_profile');
                fd.append('profile_pic', input.files[0]);
                const currentName = document.getElementById('user-name').textContent;
                fd.append('full_name', currentName);

                const res = await api(fd);
                if (res.success) {
                    loadStats();
                } else {
                    alert(res.message);
                }
            }
        }


        // ------------------------------------------
        // LÓGICA DE DETALLE DE LISTA DE REPRODUCCIÓN
        // ------------------------------------------
        let currentPlaylistId = null;
        let allSongsCache = [];

        function openPlaylistDetail(id) {
            currentPlaylistId = id;
            document.getElementById('playlists-list-view').classList.add('hidden');
            document.getElementById('playlist-detail-view').classList.remove('hidden');
            loadPlaylistItems(id);
        }

        function closePlaylistDetail() {
            currentPlaylistId = null;
            document.getElementById('playlists-list-view').classList.remove('hidden');
            document.getElementById('playlist-detail-view').classList.add('hidden');
        }

        function loadPlaylistItems(id) {
            const fd = new FormData();
            fd.append('action', 'get_playlist_details');
            fd.append('playlist_id', id);

            api(fd).then(data => {
                if(data.success) {
                    document.getElementById('detail-playlist-name').textContent = data.playlist.name;
                    document.getElementById('detail-playlist-count').textContent = `${data.items.length} canciones`;
                    
                    const list = document.getElementById('playlist-items-list');
                    list.innerHTML = '';

                    if(data.items.length > 0) {
                        data.items.forEach(item => {
                            list.innerHTML += `
                                <div class="playlist-item flex items-center gap-4 p-3 hover:bg-white/5 rounded-lg transition group bg-black/20 mb-2 border border-transparent hover:border-white/10" data-id="${item.item_id}">
                                    <div class="cursor-grab active:cursor-grabbing text-gray-600 hover:text-white p-2">
                                        <i data-lucide="grip-vertical" class="w-5 h-5"></i>
                                    </div>
                                    <img src="${item.cover_path}" class="w-10 h-10 rounded object-cover pointer-events-none">
                                    <div class="flex-1">
                                        <p class="font-medium text-white">${item.title}</p>
                                        <p class="text-xs text-gray-400">${item.artist}</p>
                                    </div>
                                    <a href="index.php?song=${item.id}" class="p-2 text-gray-400 hover:text-yellow-500 opacity-0 group-hover:opacity-100 transition">
                                        <i data-lucide="play" class="w-5 h-5"></i>
                                    </a>
                                </div>
                            `;
                        });
                        
                        // Inicializar Sortable
                        new Sortable(list, {
                            animation: 150,
                            handle: '.cursor-grab', // Selector de manejador de arrastre
                            ghostClass: 'bg-yellow-500/10',
                            onEnd: function (evt) {
                                savePlaylistOrder(id);
                            }
                        });

                    } else {
                        list.innerHTML = '<p class="text-center text-gray-500 py-10">Esta lista está vacía.</p>';
                    }
                    lucide.createIcons();
                }
            });
        }

        function savePlaylistOrder(playlistId) {
            const list = document.getElementById('playlist-items-list');
            const items = Array.from(list.querySelectorAll('.playlist-item')).map(el => el.getAttribute('data-id'));
            
            const fd = new FormData();
            fd.append('action', 'reorder_playlist');
            fd.append('playlist_id', playlistId);
            items.forEach(id => fd.append('items[]', id)); // Enviar como array

            api(fd).then(res => {
                if(!res.success) console.error('Error saving order', res);
            });
        }

        // ------------------------------------------
        // LÓGICA DE AÑADIR CANCIÓN
        // ------------------------------------------


        function openRenamePlaylistModal() {
            if(!currentPlaylistId) return;
            const currentName = document.getElementById('detail-playlist-name').textContent;
            
            openModal('Renombrar Lista', 'Nuevo nombre', currentName, async (newName) => {
                const fd = new FormData();
                fd.append('action', 'rename_playlist');
                fd.append('playlist_id', currentPlaylistId);
                fd.append('name', newName);
                
                const res = await api(fd);
                if (res.success) {
                    document.getElementById('detail-playlist-name').textContent = newName;
                    loadPlaylists(); // Refresh list view
                } else {
                    alert(res.message);
                }
            });
        }

        let confirmCallback = null;

        function confirmDeletePlaylist() {
            if(!currentPlaylistId) return;
            const modal = document.getElementById('confirm-modal');
            const content = document.getElementById('confirm-modal-content');
            const btn = document.getElementById('confirm-modal-action');
            
            btn.onclick = async () => {
                 const fd = new FormData();
                fd.append('action', 'delete_playlist');
                fd.append('playlist_id', currentPlaylistId);
                
                const res = await api(fd);
                if(res.success) {
                    closeConfirmModal();
                    closePlaylistDetail();
                    loadPlaylists();
                    loadStats();
                } else {
                    alert(res.message);
                }
            };

            modal.classList.remove('hidden');
            setTimeout(() => {
                modal.classList.remove('opacity-0');
                content.classList.remove('scale-95', 'opacity-0');
                content.classList.add('scale-100', 'opacity-100');
            }, 10);
        }

        function closeConfirmModal() {
            const modal = document.getElementById('confirm-modal');
            const content = document.getElementById('confirm-modal-content');
            
            modal.classList.add('opacity-0');
            content.classList.remove('scale-100', 'opacity-100');
            content.classList.add('scale-95', 'opacity-0');
            setTimeout(() => modal.classList.add('hidden'), 300);
        }

        function openAddSongModal() {
            const modal = document.getElementById('song-selector-modal');
            const content = document.getElementById('song-selector-content');
            
            modal.classList.remove('hidden');
            setTimeout(() => {
                modal.classList.remove('opacity-0');
                content.classList.remove('scale-95', 'opacity-0');
                content.classList.add('scale-100', 'opacity-100');
            }, 10);

            loadAllSongs();
        }

        function closeSongSelector() {
            const modal = document.getElementById('song-selector-modal');
            const content = document.getElementById('song-selector-content');
            
            modal.classList.add('opacity-0');
            content.classList.remove('scale-100', 'opacity-100');
            content.classList.add('scale-95', 'opacity-0');
            setTimeout(() => modal.classList.add('hidden'), 300);
        }

        function loadAllSongs() {
            const fd = new FormData();
            fd.append('action', 'get_all_songs');

            api(fd).then(data => {
                if(data.success) {
                    allSongsCache = data.songs;
                    renderSongSelector(data.songs);
                }
            });
        }

        function renderSongSelector(songs) {
            const container = document.getElementById('song-selector-list');
            container.innerHTML = '';
            
            songs.forEach(song => {
                const el = document.createElement('div');
                el.className = 'flex items-center gap-3 p-3 hover:bg-white/10 rounded-lg transition cursor-pointer border border-transparent hover:border-white/10';
                el.onclick = () => addSongToPlaylist(song.id);
                el.innerHTML = `
                    <img src="${song.cover_path}" class="w-10 h-10 rounded object-cover">
                    <div class="flex-1">
                        <p class="font-medium text-white text-sm">${song.title}</p>
                        <p class="text-xs text-gray-400">${song.artist}</p>
                    </div>
                    <div class="w-8 h-8 rounded-full bg-white/10 flex items-center justify-center text-white hover:bg-yellow-500 hover:text-black transition">
                        <i data-lucide="plus" class="w-4 h-4"></i>
                    </div>
                `;
                container.appendChild(el);
            });
            lucide.createIcons();
        }

        async function addSongToPlaylist(songId) {
            if(!currentPlaylistId) return;

            const fd = new FormData();
            fd.append('action', 'add_playlist_item');
            fd.append('playlist_id', currentPlaylistId);
            fd.append('song_id', songId);

            const res = await api(fd);
            if(res.success) {
                // Refresh playlist items
                loadPlaylistItems(currentPlaylistId);
                // Notification
                showToast('Canción añadida');
            } else {
                alert(res.message);
            }
        }

        // Filter search
        document.getElementById('song-search-input').addEventListener('input', (e) => {
            const term = e.target.value.toLowerCase();
            const filtered = allSongsCache.filter(s => 
                s.title.toLowerCase().includes(term) || 
                s.artist.toLowerCase().includes(term)
            );
            renderSongSelector(filtered);
        });

        // ------------------------------------------
        // PASSWORD CHANGE LOGIC
        // ------------------------------------------
        function openChangePasswordModal() {
            const modal = document.getElementById('password-modal');
            const content = document.getElementById('password-modal-content');
            
            // Clear inputs
            document.getElementById('current-password').value = '';
            document.getElementById('new-password').value = '';
            document.getElementById('confirm-password').value = '';
            
            // Toggle Current Password visibility based on user type
            const currentPwdContainer = document.getElementById('current-password').closest('div').parentElement;
            if (window.isGoogleUser) {
                currentPwdContainer.classList.add('hidden');
            } else {
                currentPwdContainer.classList.remove('hidden');
            }
            
            
            modal.classList.remove('hidden');
            setTimeout(() => {
                modal.classList.remove('opacity-0');
                content.classList.remove('scale-95', 'opacity-0');
                content.classList.add('scale-100', 'opacity-100');
            }, 10);
        }

        function closePasswordModal() {
            const modal = document.getElementById('password-modal');
            const content = document.getElementById('password-modal-content');
            
            modal.classList.add('opacity-0');
            content.classList.remove('scale-100', 'opacity-100');
            content.classList.add('scale-95', 'opacity-0');
            setTimeout(() => modal.classList.add('hidden'), 300);
        }

        // Real-time password validation
        document.addEventListener('DOMContentLoaded', () => {
            const newPasswordInput = document.getElementById('new-password');
            if (newPasswordInput) {
                newPasswordInput.addEventListener('input', function() {
                    const val = this.value;
                    
                    const reqLower = document.getElementById('pwd-req-lower');
                    const reqUpper = document.getElementById('pwd-req-upper');
                    const reqSpecial = document.getElementById('pwd-req-special');
                    
                    // Check Lowercase
                    if (/[a-z]/.test(val)) {
                        setValidReq(reqLower);
                    } else {
                        setInvalidReq(reqLower);
                    }

                    // Check Uppercase
                    if (/[A-Z]/.test(val)) {
                        setValidReq(reqUpper);
                    } else {
                        setInvalidReq(reqUpper);
                    }

                    // Check Special
                    if (/[^A-Za-z0-9]/.test(val)) {
                        setValidReq(reqSpecial);
                    } else {
                        setInvalidReq(reqSpecial);
                    }
                });
            }
        });

        function setValidReq(el) {
            const dot = el.querySelector('div');
            const text = el.querySelector('span');
            dot.classList.remove('bg-gray-600');
            dot.classList.add('bg-green-500', 'shadow-[0_0_5px_#22c55e]');
            text.classList.add('text-green-400');
            text.classList.remove('text-gray-500');
        }

        function setInvalidReq(el) {
            const dot = el.querySelector('div');
            const text = el.querySelector('span');
            dot.classList.add('bg-gray-600');
            dot.classList.remove('bg-green-500', 'shadow-[0_0_5px_#22c55e]');
            text.classList.remove('text-green-400');
            text.classList.add('text-gray-500');
        }

        async function changePassword() {
            const currentPwd = document.getElementById('current-password').value;
            const newPwd = document.getElementById('new-password').value;
            const confirmPwd = document.getElementById('confirm-password').value;



            // Validación: Requerir contraseña actual solo si NO es lógica de usuario de google (verificación frontend)
            // Pero mejor, verificar si el campo está oculto.
            const isHidden = document.getElementById('current-password').closest('div').parentElement.classList.contains('hidden');
            
            if ((!isHidden && !currentPwd) || !newPwd || !confirmPwd) {
                alert('Por favor completa todos los campos');
                return;
            }

            if (newPwd !== confirmPwd) {
                alert('Las contraseñas nuevas no coinciden');
                return;
            }

            // Validate complexity
            if (!(/^(?=.*[a-z])(?=.*[A-Z])(?=.*[^a-zA-Z0-9]).+$/.test(newPwd))) {
                alert('La contraseña no cumple con los requisitos de seguridad');
                return;
            }

            const fd = new FormData();
            fd.append('action', 'change_password');
            fd.append('current_password', currentPwd);
            fd.append('new_password', newPwd);

            const res = await api(fd);
            if (res.success) {
                alert('Contraseña cambiada exitosamente');
                closePasswordModal();
            } else {
                alert(res.message || 'Error al cambiar la contraseña');
            }
        }

    </script>
</body>
</html>
