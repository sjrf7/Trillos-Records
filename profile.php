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
        .tab-inactive:hover { color: #fff; }
    </style>
</head>
<body class="min-h-screen pb-20">

    <!-- Navbar Simple -->
    <nav class="fixed top-0 w-full z-50 glass-panel border-b border-white/5 bg-black/80">
        <div class="max-w-7xl mx-auto px-4 py-4 flex justify-between items-center">
            <a href="index.php" class="flex items-center gap-2 text-yellow-500 hover:text-yellow-400 transition">
                <i data-lucide="arrow-left" class="w-5 h-5"></i>
                <span class="font-medium">Volver al Inicio</span>
            </a>
            <div class="flex items-center gap-4">
               <a href="logout.php" class="text-sm text-gray-400 hover:text-white transition">Cerrar Sesión</a>
            </div>
        </div>
    </nav>

    <main class="max-w-4xl mx-auto pt-24 px-4">
        
        <!-- Profile Header -->
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

            <!-- Info -->
            <div class="flex-1 text-center md:text-left">
                <div class="flex items-center justify-center md:justify-start gap-3 mb-2">
                    <h1 id="user-name" class="text-3xl font-bold text-white brand-title">Cargando...</h1>
                    <button onclick="openEditNameModal()" class="text-gray-500 hover:text-yellow-500 transition">
                        <i data-lucide="edit-2" class="w-4 h-4"></i>
                    </button>
                </div>
                <p class="text-gray-400 mb-6">Miembro de Trillos Visual Records</p>
                
                <!-- Stats -->
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
            </div>
        </div>

        <!-- Navigation Tabs -->
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

        <!-- Content Sections -->
        
        <!-- Playlists Section -->
        <div id="section-playlists" class="space-y-6">
            <!-- Playlist List View -->
            <div id="playlists-list-view">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-xl font-bold text-white">Listas de Reproducción</h2>
                    <button onclick="openCreatePlaylistModal()" class="bg-yellow-500/10 hover:bg-yellow-500 text-yellow-500 hover:text-black px-4 py-2 rounded-lg transition flex items-center gap-2 text-sm font-medium">
                        <i data-lucide="plus" class="w-4 h-4"></i> Nueva Lista
                    </button>
                </div>
                
                <div id="playlists-grid" class="grid grid-cols-2 md:grid-cols-3 gap-4">
                    <!-- Dynamic Content -->
                </div>
            </div>

            <!-- Playlist Detail View -->
            <div id="playlist-detail-view" class="hidden">
                 <div class="flex items-center gap-4 mb-6">
                    <button onclick="closePlaylistDetail()" class="p-2 rounded-full hover:bg-white/10 text-gray-400 hover:text-white transition">
                        <i data-lucide="arrow-left" class="w-6 h-6"></i>
                    </button>
                    <div>
                        <h2 id="detail-playlist-name" class="text-2xl font-bold text-white">Playlist Name</h2>
                        <span id="detail-playlist-count" class="text-sm text-gray-500">0 canciones</span>
                    </div>
                    <button onclick="openAddSongModal()" class="ml-auto bg-white text-black hover:bg-gray-200 px-4 py-2 rounded-full font-medium flex items-center gap-2 transition">
                        <i data-lucide="plus" class="w-4 h-4"></i> Añadir Canciones
                    </button>
                </div>

                <div id="playlist-items-list" class="space-y-2">
                    <!-- Dynamic Items -->
                </div>
            </div>
        </div>

        <!-- Likes Section -->
        <div id="section-likes" class="hidden space-y-8">
            <!-- Liked Songs -->
            <div>
                <h3 class="text-lg font-bold text-gray-300 mb-4 flex items-center gap-2">
                    <i data-lucide="music" class="w-5 h-5 text-yellow-500"></i> Canciones
                </h3>
                <div id="liked-songs-grid" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- Dynamic -->
                </div>
            </div>
            <!-- Liked Videos -->
            <div>
                <h3 class="text-lg font-bold text-gray-300 mb-4 flex items-center gap-2">
                    <i data-lucide="video" class="w-5 h-5 text-yellow-500"></i> Videos
                </h3>
                <div id="liked-videos-grid" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- Dynamic -->
                </div>
            </div>
        </div>

        <!-- History Section -->
        <div id="section-history" class="hidden space-y-6">
            <h2 class="text-xl font-bold text-white">Actividad Reciente</h2>
            <div id="history-list" class="space-y-2">
                <!-- Dynamic -->
            </div>
        </div>

    </main>

    <!-- Generic Modal Template -->
    <div id="generic-modal" class="fixed inset-0 bg-black/80 hidden z-[60] flex items-center justify-center opacity-0 transition-opacity duration-300">
        <div id="generic-modal-content" class="bg-[#111] border border-gray-800 rounded-2xl p-8 max-w-sm w-full transform scale-95 opacity-0 transition-all duration-300 relative">
            <button onclick="closeGenericModal()" class="absolute top-4 right-4 text-gray-400 hover:text-white">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
            <h3 id="generic-modal-title" class="text-xl font-bold text-white mb-6">Título</h3>
            <div id="generic-modal-body">
                <!-- Dynamic Input -->
            </div>
            <div class="flex justify-end gap-3 mt-6">
                <button onclick="closeGenericModal()" class="px-4 py-2 text-gray-400 hover:text-white text-sm">Cancelar</button>
                <button id="generic-modal-action" class="px-4 py-2 bg-yellow-500 text-black font-medium rounded-lg hover:bg-yellow-400">Guardar</button>
            </div>
        </div>
    </div>

    <!-- Song Selector Modal -->
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
                <!-- Songs List -->
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script>
        lucide.createIcons();

        // Load Initial Data
        document.addEventListener('DOMContentLoaded', () => {
            loadStats();
            loadPlaylists(); 
        });

        // ------------------------------------------
        // TABS LOGIC
        // ------------------------------------------
        function switchTab(tabName) {
            // Update Tab Styles
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
        // API CALLS
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
                    document.getElementById('user-name').textContent = data.user.full_name;
                    if (data.user.profile_pic) {
                        document.getElementById('profile-pic').src = data.user.profile_pic;
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
                        el.onclick = () => openPlaylistDetail(pl.id); // Add click handler
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
        // MODALS LOGIC
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
            
            // Focus input after modal opens
            setTimeout(() => document.getElementById('modal-input').focus(), 100);

            modalAction.onclick = () => {
                const val = document.getElementById('modal-input').value;
                if(val) onConfirm(val);
                closeGenericModal();
            };

            // Enter key support
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
        // ACTIONS (USING MODALS)
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
        // PLAYLIST DETAIL LOGIC
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
                        
                        // Initialize Sortable
                        new Sortable(list, {
                            animation: 150,
                            handle: '.cursor-grab', // Drag handle selector
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
            items.forEach(id => fd.append('items[]', id)); // Send as array

            api(fd).then(res => {
                if(!res.success) console.error('Error saving order', res);
            });
        }

        // ------------------------------------------
        // ADD SONG LOGIC
        // ------------------------------------------
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
                // Maybe close modal or show checkmark? Let's keep open to add more.
                alert('Canción añadida'); // Temporary feedback, could be better toast
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

    </script>
</body>
</html>
