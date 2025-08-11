<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Header -->
    <div class="mb-8 animate-fade-in">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-4xl font-bold gradient-text">Tableau de bord</h1>
                <p class="text-gray-400 mt-2">Bienvenue, <?php echo htmlspecialchars($_SESSION['discord_user']['username']); ?></p>
                <p class="text-neon-blue">Domaine : <?php echo htmlspecialchars($domainName); ?></p>
            </div>
            <div class="hidden md:block">
                <img src="<?php echo $_SESSION['discord_user']['avatar']; ?>" 
                     alt="Avatar" 
                     class="h-16 w-16 rounded-full border-2 border-neon-blue animate-glow">
            </div>
        </div>
    </div>
    
    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <!-- Active Missions -->
        <div class="glass-effect rounded-xl p-6 hover-glow animate-fade-in">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-neon-blue/20">
                    <svg class="h-8 w-8 text-neon-blue" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-400">Missions actives</p>
                    <p class="text-2xl font-bold text-white"><?php echo count($activeMissions); ?>/3</p>
                </div>
            </div>
        </div>
        
        <!-- Available Missions -->
        <div class="glass-effect rounded-xl p-6 hover-glow animate-fade-in" style="animation-delay: 0.1s">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-neon-green/20">
                    <svg class="h-8 w-8 text-neon-green" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-400">Missions disponibles</p>
                    <p class="text-2xl font-bold text-white"><?php echo $totalAvailableMissions; ?></p>
                </div>
            </div>
        </div>
        
        <!-- Domain -->
        <div class="glass-effect rounded-xl p-6 hover-glow animate-fade-in" style="animation-delay: 0.2s">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-neon-purple/20">
                    <svg class="h-8 w-8 text-neon-purple" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-400">Votre domaine</p>
                    <p class="text-lg font-bold text-white"><?php echo htmlspecialchars($domainName); ?></p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Active Missions Section -->
    <?php if (!empty($activeMissions)): ?>
        <div class="mb-8 animate-fade-in" style="animation-delay: 0.3s">
            <h2 class="text-2xl font-bold text-white mb-6 flex items-center">
                <span class="text-neon-blue mr-2">⚡</span>
                Vos missions en cours
            </h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach ($activeMissions as $mission): ?>
                    <div class="glass-effect rounded-xl p-6 hover-glow group">
                        <div class="flex items-start justify-between mb-4">
                            <h3 class="text-lg font-semibold text-white group-hover:text-neon-blue transition-colors">
                                <?php echo htmlspecialchars($mission['name']); ?>
                            </h3>
                            <span class="px-2 py-1 text-xs rounded-full bg-yellow-500/20 text-yellow-400 border border-yellow-500/30">
                                En cours
                            </span>
                        </div>
                        
                        <div class="space-y-2 mb-4">
                            <div class="flex items-center text-sm text-gray-400">
                                <svg class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                                <?php echo date('d/m/Y', strtotime($mission['deadline'])); ?>
                            </div>
                            <div class="flex items-center text-sm">
                                <span class="px-2 py-1 rounded-full text-xs
                                    <?php echo $mission['difficulty'] === 'facile' ? 'bg-green-500/20 text-green-400' : 
                                        ($mission['difficulty'] === 'normal' ? 'bg-yellow-500/20 text-yellow-400' : 'bg-red-500/20 text-red-400'); ?>">
                                    <?php echo ucfirst($mission['difficulty']); ?>
                                </span>
                            </div>
                        </div>
                        
                        <a href="/missions/view/<?php echo $mission['id']; ?>" 
                           class="inline-flex items-center text-neon-blue hover:text-white transition-colors">
                            Voir les détails
                            <svg class="h-4 w-4 ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
    
    <!-- Recent Available Missions -->
    <div class="animate-fade-in" style="animation-delay: 0.4s">
        <div class="flex items-center justify-between mb-6">
            <h2 class="text-2xl font-bold text-white flex items-center">
                <span class="text-neon-green mr-2">🎯</span>
                Missions disponibles
            </h2>
            <a href="/missions" class="text-neon-blue hover:text-white transition-colors flex items-center">
                Voir toutes
                <svg class="h-4 w-4 ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
            </a>
        </div>
        
        <?php if (empty($recentMissions)): ?>
            <div class="glass-effect rounded-xl p-8 text-center">
                <div class="text-gray-400 mb-4">
                    <svg class="h-16 w-16 mx-auto" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                    </svg>
                </div>
                <h3 class="text-lg font-medium text-white mb-2">Aucune mission disponible</h3>
                <p class="text-gray-400">Toutes les missions sont actuellement assignées ou terminées.</p>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach ($recentMissions as $name => $missionGroup): ?>
                    <div class="glass-effect rounded-xl p-6 hover-glow group relative overflow-hidden">
                        <!-- Availability badge -->
                        <div class="absolute -top-2 -right-2 bg-gradient-to-r from-neon-green to-neon-blue text-white px-3 py-1 rounded-full text-sm font-bold">
                            <?php echo $missionGroup['available_count'] . '/' . $missionGroup['count']; ?>
                        </div>
                        
                        <div class="mb-4">
                            <h3 class="text-lg font-semibold text-white group-hover:text-neon-blue transition-colors mb-2">
                                <?php echo htmlspecialchars($name); ?>
                            </h3>
                            
                            <div class="flex items-center space-x-4 text-sm text-gray-400">
                                <div class="flex items-center">
                                    <svg class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                    <?php echo date('d/m/Y', strtotime($missionGroup['info']['deadline'])); ?>
                                </div>
                                <span class="px-2 py-1 rounded-full text-xs
                                    <?php echo $missionGroup['info']['difficulty'] === 'facile' ? 'bg-green-500/20 text-green-400' : 
                                        ($missionGroup['info']['difficulty'] === 'normal' ? 'bg-yellow-500/20 text-yellow-400' : 'bg-red-500/20 text-red-400'); ?>">
                                    <?php echo ucfirst($missionGroup['info']['difficulty']); ?>
                                </span>
                            </div>
                        </div>
                        
                        <div class="flex items-center justify-between">
                            <?php if ($missionGroup['available_count'] > 0): ?>
                                <span class="text-neon-green text-sm font-medium">
                                    ✅ <?php echo $missionGroup['available_count']; ?> disponible<?php echo $missionGroup['available_count'] > 1 ? 's' : ''; ?>
                                </span>
                            <?php else: ?>
                                <span class="text-red-400 text-sm font-medium">
                                    ❌ Aucun exemplaire disponible
                                </span>
                            <?php endif; ?>
                            
                            <a href="/missions/view/<?php echo $missionGroup['info']['id']; ?>" 
                               class="text-neon-blue hover:text-white transition-colors">
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                </svg>
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Quick Actions -->
    <div class="mt-12 animate-fade-in" style="animation-delay: 0.5s">
        <h2 class="text-2xl font-bold text-white mb-6 flex items-center">
            <span class="text-neon-purple mr-2">🚀</span>
            Actions rapides
        </h2>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <a href="/uploads" class="glass-effect rounded-xl p-6 hover-glow group text-center">
                <div class="text-3xl mb-2">📤</div>
                <h3 class="font-semibold text-white group-hover:text-neon-blue transition-colors">Upload</h3>
                <p class="text-sm text-gray-400">Envoyer vos fichiers</p>
            </a>
            
            <a href="/files" class="glass-effect rounded-xl p-6 hover-glow group text-center">
                <div class="text-3xl mb-2">📁</div>
                <h3 class="font-semibold text-white group-hover:text-neon-blue transition-colors">Fichiers</h3>
                <p class="text-sm text-gray-400">Parcourir les uploads</p>
            </a>
            
            <a href="/gallery" class="glass-effect rounded-xl p-6 hover-glow group text-center">
                <div class="text-3xl mb-2">🎨</div>
                <h3 class="font-semibold text-white group-hover:text-neon-blue transition-colors">Galerie</h3>
                <p class="text-sm text-gray-400">Assets 3D</p>
            </a>
            
            <a href="/faq" class="glass-effect rounded-xl p-6 hover-glow group text-center">
                <div class="text-3xl mb-2">❓</div>
                <h3 class="font-semibold text-white group-hover:text-neon-blue transition-colors">FAQ</h3>
                <p class="text-sm text-gray-400">Questions fréquentes</p>
            </a>
        </div>
    </div>
</div>