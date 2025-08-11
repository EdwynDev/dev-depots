<div class="min-h-screen flex items-center justify-center bg-gradient-to-br from-cyber-dark via-cyber-gray to-cyber-light">
    <!-- Animated background elements -->
    <div class="absolute inset-0 overflow-hidden">
        <div class="absolute -top-40 -right-40 w-80 h-80 bg-neon-blue/10 rounded-full blur-3xl animate-pulse"></div>
        <div class="absolute -bottom-40 -left-40 w-80 h-80 bg-neon-purple/10 rounded-full blur-3xl animate-pulse delay-1000"></div>
        <div class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 w-96 h-96 bg-neon-green/5 rounded-full blur-3xl animate-pulse delay-2000"></div>
    </div>
    
    <div class="relative z-10 max-w-md w-full mx-4">
        <!-- Logo and title -->
        <div class="text-center mb-8 animate-fade-in">
            <img src="/INCLUDE/favicon.png" alt="Logo" class="mx-auto h-20 w-20 mb-4 animate-float">
            <h1 class="text-4xl font-bold gradient-text mb-2">NEO POLY WORKS</h1>
            <p class="text-xl text-gray-300 neon-text">DEPOTS</p>
        </div>
        
        <!-- Login card -->
        <div class="glass-effect rounded-2xl p-8 shadow-2xl animate-slide-in">
            <div class="text-center mb-6">
                <h2 class="text-2xl font-bold text-white mb-2">Connexion</h2>
                <p class="text-gray-400">Connectez-vous avec Discord pour accéder à la plateforme</p>
            </div>
            
            <!-- Discord login button -->
            <a href="<?php echo htmlspecialchars($discordUrl); ?>" 
               class="w-full flex items-center justify-center px-6 py-4 bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 text-white font-semibold rounded-xl transition-all duration-300 hover-glow group">
                <svg class="w-6 h-6 mr-3 group-hover:animate-pulse" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M20.317 4.37a19.791 19.791 0 0 0-4.885-1.515a.074.074 0 0 0-.079.037c-.21.375-.444.864-.608 1.25a18.27 18.27 0 0 0-5.487 0a12.64 12.64 0 0 0-.617-1.25a.077.077 0 0 0-.079-.037A19.736 19.736 0 0 0 3.677 4.37a.07.07 0 0 0-.032.027C.533 9.046-.32 13.58.099 18.057a.082.082 0 0 0 .031.057a19.9 19.9 0 0 0 5.993 3.03a.078.078 0 0 0 .084-.028a14.09 14.09 0 0 0 1.226-1.994a.076.076 0 0 0-.041-.106a13.107 13.107 0 0 1-1.872-.892a.077.077 0 0 1-.008-.128a10.2 10.2 0 0 0 .372-.292a.074.074 0 0 1 .077-.01c3.928 1.793 8.18 1.793 12.062 0a.074.074 0 0 1 .078.01c.12.098.246.198.373.292a.077.077 0 0 1-.006.127a12.299 12.299 0 0 1-1.873.892a.077.077 0 0 0-.041.107c.36.698.772 1.362 1.225 1.993a.076.076 0 0 0 .084.028a19.839 19.839 0 0 0 6.002-3.03a.077.077 0 0 0 .032-.054c.5-5.177-.838-9.674-3.549-13.66a.061.061 0 0 0-.031-.03zM8.02 15.33c-1.183 0-2.157-1.085-2.157-2.419c0-1.333.956-2.419 2.157-2.419c1.21 0 2.176 1.096 2.157 2.42c0 1.333-.956 2.418-2.157 2.418zm7.975 0c-1.183 0-2.157-1.085-2.157-2.419c0-1.333.955-2.419 2.157-2.419c1.21 0 2.176 1.096 2.157 2.42c0 1.333-.946 2.418-2.157 2.418z"/>
                </svg>
                Se connecter avec Discord
            </a>
            
            <!-- Error message -->
            <div class="mt-6 p-4 glass-effect rounded-lg border border-red-500/20">
                <div class="flex items-start">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-red-400">Accès restreint</h3>
                        <div class="mt-2 text-sm text-red-300">
                            <p>Si vous n'arrivez pas à vous connecter :</p>
                            <ul class="list-disc list-inside mt-1 space-y-1">
                                <li>Vous n'êtes pas encore enregistré dans le système</li>
                            </ul>
                            <p class="mt-2 italic">Demandez à votre chef de pôle de vous ajouter !</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Footer -->
        <div class="text-center mt-8 text-gray-500">
            <p>&copy; 2024 NEO POLY WORKS. Tous droits réservés.</p>
        </div>
    </div>
</div>