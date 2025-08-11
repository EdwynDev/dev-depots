<!DOCTYPE html>
<html lang="fr" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="/INCLUDE/favicon.png" type="image/png">
    <title><?php echo $title ?? 'NEO POLY WORKS - DEPOTS'; ?></title>
    
    <!-- Meta tags pour les embeds -->
    <meta property="og:title" content="NEO POLY WORKS - DEPOTS">
    <meta property="og:description" content="Plateforme de gestion de missions et de dépôts de fichiers pour les participants au projet NEO POLY WORKS.">
    <meta property="og:image" content="/INCLUDE/favicon.png">
    <meta property="og:url" content="https://devdepots.neopolyworks.fr/">
    <meta property="og:type" content="website">
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: {
                        'exo': ['Exo2-Regular', 'sans-serif']
                    },
                    colors: {
                        'neon-blue': '#00f5ff',
                        'neon-purple': '#bf00ff',
                        'neon-green': '#39ff14',
                        'cyber-dark': '#0a0a0f',
                        'cyber-gray': '#1a1a2e',
                        'cyber-light': '#16213e'
                    },
                    animation: {
                        'glow': 'glow 2s ease-in-out infinite alternate',
                        'float': 'float 3s ease-in-out infinite',
                        'slide-in': 'slideIn 0.5s ease-out',
                        'fade-in': 'fadeIn 0.3s ease-out'
                    },
                    keyframes: {
                        glow: {
                            '0%': { boxShadow: '0 0 5px #00f5ff, 0 0 10px #00f5ff, 0 0 15px #00f5ff' },
                            '100%': { boxShadow: '0 0 10px #00f5ff, 0 0 20px #00f5ff, 0 0 30px #00f5ff' }
                        },
                        float: {
                            '0%, 100%': { transform: 'translateY(0px)' },
                            '50%': { transform: 'translateY(-10px)' }
                        },
                        slideIn: {
                            '0%': { transform: 'translateX(-100%)', opacity: '0' },
                            '100%': { transform: 'translateX(0)', opacity: '1' }
                        },
                        fadeIn: {
                            '0%': { opacity: '0' },
                            '100%': { opacity: '1' }
                        }
                    }
                }
            }
        }
    </script>
    
    <!-- Custom CSS -->
    <style>
        @font-face {
            font-family: 'Exo2-Regular';
            src: url('/FONTS/Exo2-Regular.otf') format('opentype');
        }
        
        * {
            font-family: 'Exo2-Regular', sans-serif;
        }
        
        .cyber-border {
            border: 1px solid transparent;
            background: linear-gradient(45deg, #00f5ff, #bf00ff) border-box;
            -webkit-mask: linear-gradient(#fff 0 0) padding-box, linear-gradient(#fff 0 0);
            -webkit-mask-composite: exclude;
            mask: linear-gradient(#fff 0 0) padding-box, linear-gradient(#fff 0 0);
            mask-composite: exclude;
        }
        
        .neon-text {
            text-shadow: 0 0 10px currentColor;
        }
        
        .glass-effect {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .hover-glow:hover {
            box-shadow: 0 0 20px rgba(0, 245, 255, 0.5);
            transform: translateY(-2px);
            transition: all 0.3s ease;
        }
        
        .gradient-text {
            background: linear-gradient(45deg, #00f5ff, #bf00ff);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
    </style>
</head>
<body class="bg-cyber-dark text-white min-h-screen font-exo">
    <!-- Navigation -->
    <?php if (isset($user) && $user): ?>
        <nav class="fixed top-0 left-0 right-0 z-50 glass-effect border-b border-neon-blue/20">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between items-center h-16">
                    <!-- Logo -->
                    <div class="flex items-center">
                        <a href="/dashboard" class="flex items-center space-x-2 hover-glow rounded-lg p-2">
                            <img src="/INCLUDE/favicon.png" alt="Logo" class="h-8 w-8">
                            <span class="text-xl font-bold gradient-text">NEO POLY</span>
                        </a>
                    </div>
                    
                    <!-- Desktop Navigation -->
                    <div class="hidden md:flex items-center space-x-4">
                        <a href="/dashboard" class="nav-link px-3 py-2 rounded-md text-sm font-medium hover:text-neon-blue transition-colors">
                            🏠 Dashboard
                        </a>
                        <a href="/missions" class="nav-link px-3 py-2 rounded-md text-sm font-medium hover:text-neon-blue transition-colors">
                            📇 Missions
                        </a>
                        <a href="/missions/secondary" class="nav-link px-3 py-2 rounded-md text-sm font-medium hover:text-neon-blue transition-colors">
                            📋 Secondaires
                        </a>
                        <a href="/uploads" class="nav-link px-3 py-2 rounded-md text-sm font-medium hover:text-neon-blue transition-colors">
                            🚀 Uploads
                        </a>
                        <a href="/files" class="nav-link px-3 py-2 rounded-md text-sm font-medium hover:text-neon-blue transition-colors">
                            🗃️ Fichiers
                        </a>
                        <a href="/gallery" class="nav-link px-3 py-2 rounded-md text-sm font-medium hover:text-neon-blue transition-colors">
                            🎨 Galerie
                        </a>
                        <?php if ($isChef): ?>
                            <a href="/admin/domain" class="nav-link px-3 py-2 rounded-md text-sm font-medium hover:text-neon-purple transition-colors">
                                ⚙️ Admin
                            </a>
                        <?php endif; ?>
                    </div>
                    
                    <!-- User Menu -->
                    <div class="flex items-center space-x-4">
                        <div class="relative">
                            <img src="<?php echo $_SESSION['discord_user']['avatar']; ?>" 
                                 alt="Avatar" 
                                 class="h-8 w-8 rounded-full border-2 border-neon-blue hover-glow cursor-pointer"
                                 onclick="toggleUserMenu()">
                            <div id="userMenu" class="hidden absolute right-0 mt-2 w-48 glass-effect rounded-md shadow-lg py-1 z-50">
                                <a href="/users/profile/<?php echo $_SESSION['discord_user']['username']; ?>" 
                                   class="block px-4 py-2 text-sm hover:bg-white/10">Profil</a>
                                <a href="/logout" class="block px-4 py-2 text-sm hover:bg-white/10">Déconnexion</a>
                            </div>
                        </div>
                        
                        <!-- Mobile menu button -->
                        <button onclick="toggleMobileMenu()" class="md:hidden p-2 rounded-md hover:bg-white/10">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Mobile Navigation -->
            <div id="mobileMenu" class="hidden md:hidden glass-effect border-t border-white/10">
                <div class="px-2 pt-2 pb-3 space-y-1">
                    <a href="/dashboard" class="block px-3 py-2 rounded-md text-base font-medium hover:bg-white/10">🏠 Dashboard</a>
                    <a href="/missions" class="block px-3 py-2 rounded-md text-base font-medium hover:bg-white/10">📇 Missions</a>
                    <a href="/missions/secondary" class="block px-3 py-2 rounded-md text-base font-medium hover:bg-white/10">📋 Secondaires</a>
                    <a href="/uploads" class="block px-3 py-2 rounded-md text-base font-medium hover:bg-white/10">🚀 Uploads</a>
                    <a href="/files" class="block px-3 py-2 rounded-md text-base font-medium hover:bg-white/10">🗃️ Fichiers</a>
                    <a href="/gallery" class="block px-3 py-2 rounded-md text-base font-medium hover:bg-white/10">🎨 Galerie</a>
                    <?php if ($isChef): ?>
                        <a href="/admin/domain" class="block px-3 py-2 rounded-md text-base font-medium hover:bg-white/10">⚙️ Admin</a>
                    <?php endif; ?>
                </div>
            </div>
        </nav>
        
        <!-- Spacer for fixed nav -->
        <div class="h-16"></div>
    <?php endif; ?>
    
    <!-- Main Content -->
    <main class="min-h-screen">
        <?php echo $content; ?>
    </main>
    
    <!-- FAQ Button -->
    <?php if (isset($user) && $user): ?>
        <a href="/faq" class="fixed bottom-8 right-8 w-14 h-14 bg-gradient-to-r from-neon-blue to-neon-purple rounded-full flex items-center justify-center text-2xl shadow-lg hover-glow z-40 animate-float">
            ❓
        </a>
    <?php endif; ?>
    
    <!-- Scripts -->
    <script>
        function toggleUserMenu() {
            const menu = document.getElementById('userMenu');
            menu.classList.toggle('hidden');
        }
        
        function toggleMobileMenu() {
            const menu = document.getElementById('mobileMenu');
            menu.classList.toggle('hidden');
        }
        
        // Close menus when clicking outside
        document.addEventListener('click', function(event) {
            const userMenu = document.getElementById('userMenu');
            const mobileMenu = document.getElementById('mobileMenu');
            
            if (!event.target.closest('.relative') && userMenu) {
                userMenu.classList.add('hidden');
            }
            
            if (!event.target.closest('button') && mobileMenu) {
                mobileMenu.classList.add('hidden');
            }
        });
        
        // Add smooth scrolling
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                document.querySelector(this.getAttribute('href')).scrollIntoView({
                    behavior: 'smooth'
                });
            });
        });
    </script>
</body>
</html>