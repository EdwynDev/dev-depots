<?php
if (session_status() == PHP_SESSION_NONE) {
    session_set_cookie_params(30 * 24 * 60 * 60);
    session_start();
}

if (!isset($_SESSION['discord_user'])) {
    header("Location: https://depots.neopolyworks.fr");
    exit;
}

require_once __DIR__ . '/CONTROLLERS/MainController.php';
require 'config.php';

use Controllers\MainController;

$mainController = new MainController();
$userId = $_SESSION['discord_user']['id'];
$isChef = $mainController->checkIfIsChefs($userId);

$search = isset($_GET['search']) ? $_GET['search'] : '';
$assets = $search ? $mainController->searchAssets3D($search) : $mainController->getAllAssets3D();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_asset'])) {
    if (isset($_FILES['asset_image']) && $_FILES['asset_image']['error'] === 0) {
        $imagePath = $mainController->uploadImage($_FILES['asset_image']);
        if ($imagePath) {
            $missionId = $_POST['mission_id'];
            $userId = $_POST['user_id'];
            $mission = $mainController->getMissionById($missionId);
            $assetName = $mission['name'];
            
            if ($mainController->addAsset3D($assetName, $missionId, $userId, $imagePath)) {
                header("Location: " . $_SERVER['PHP_SELF']);
                exit;
            }
        }
    }
}

// Add new POST check for deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_asset'])) {
    $assetId = $_POST['asset_id'];
    if ($isChef && $mainController->deleteAsset3D($assetId)) {
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }
}

require_once __DIR__ . "/INCLUDE/header.php";
?>

<div class="container mx-auto px-4">
    <h1 class="text-2xl font-bold mb-6">🎨 Galerie d'Assets 3D</h1>

    <form method="GET" class="mb-6">
        <div class="flex gap-4">
            <input type="text" 
                   name="search" 
                   placeholder="Rechercher par nom de mission ou créateur..." 
                   value="<?php echo htmlspecialchars($search); ?>"
                   class="flex-1 px-4 py-2 bg-gray-700 text-white rounded-lg">
            <button type="submit" 
                    class="px-6 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition-colors">
                Rechercher
            </button>
        </div>
    </form>

    <?php if ($isChef): ?>
    <form method="POST" enctype="multipart/form-data" class="mb-8 bg-gray-800 p-6 rounded-lg">
        <h2 class="text-xl font-bold mb-4">Ajouter un nouvel asset</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <input type="file"
                   name="asset_image" 
                   accept="image/*" 
                   required
                   class="px-4 py-2 bg-gray-700 text-white rounded-lg">
            
            <div class="relative">
                <input type="text"
                       id="mission_search"
                       placeholder="Mission de l'asset (Écrire ici)..." 
                       class="w-full px-4 py-2 bg-gray-700 text-white rounded-lg">
                <input type="hidden" name="mission_id" id="mission_id" required>
                <div id="mission_results" class="absolute w-full mt-1 bg-gray-800 rounded-lg shadow-lg hidden max-h-48 overflow-y-auto z-10"></div>
            </div>

            <div class="relative">
                <input type="text"
                       id="user_search"
                       placeholder="Créateur de l'asset (Écrire ici)..." 
                       class="w-full px-4 py-2 bg-gray-700 text-white rounded-lg">
                <input type="hidden" name="user_id" id="user_id" required>
                <div id="user_results" class="absolute w-full mt-1 bg-gray-800 rounded-lg shadow-lg hidden max-h-48 overflow-y-auto z-10"></div>
            </div>

            <button type="submit" 
                    name="add_asset"
                    class="px-6 py-2 bg-green-500 text-white rounded-lg hover:bg-green-600 transition-colors">
                Ajouter l'asset
            </button>
        </div>
    </form>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        setupAutocomplete(
            'mission_search',
            'mission_results',
            'mission_id',
            <?php echo json_encode($mainController->getAssignedMissionsWithUsers()); ?>,
            mission => `${mission.username}(${mission.assignee_id}) - ${mission.name}`,
            'id'
        );
        setupAutocomplete(
            'user_search',
            'user_results',
            'user_id',
            <?php echo json_encode($mainController->getAllMembers()); ?>,
            user => user.username,
            'userId'
        );

        function setupAutocomplete(inputId, resultsId, hiddenInputId, data, labelFormatter, valueKey) {
            const searchInput = document.getElementById(inputId);
            const resultsDiv = document.getElementById(resultsId);
            const hiddenInput = document.getElementById(hiddenInputId);

            searchInput.addEventListener('input', function() {
                const searchTerm = this.value.toLowerCase();
                const matches = data.filter(item => 
                    labelFormatter(item).toLowerCase().includes(searchTerm)
                );

                resultsDiv.innerHTML = '';
                if (matches.length > 0 && searchTerm) {
                    resultsDiv.classList.remove('hidden');
                    matches.forEach(item => {
                        const div = document.createElement('div');
                        div.className = 'p-2 hover:bg-gray-700 cursor-pointer';
                        div.textContent = labelFormatter(item);
                        div.addEventListener('click', () => {
                            searchInput.value = labelFormatter(item);
                            hiddenInput.value = item[valueKey];
                            resultsDiv.classList.add('hidden');
                        });
                        resultsDiv.appendChild(div);
                    });
                } else {
                    resultsDiv.classList.add('hidden');
                }
            });

            document.addEventListener('click', function(e) {
                if (!searchInput.contains(e.target) && !resultsDiv.contains(e.target)) {
                    resultsDiv.classList.add('hidden');
                }
            });
        }
    });
    </script>
    <?php endif; ?>

    <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-4 gap-6">
        <?php foreach ($assets as $asset): ?>
            <div class="bg-gray-800 rounded-lg overflow-hidden">
                <img src="<?php echo htmlspecialchars($asset['image_path']); ?>" 
                     alt="Asset 3D" 
                     class="w-full h-48 object-cover">
                <div class="p-4">
                    <h3 class="font-bold text-lg mb-2"><?php echo htmlspecialchars($asset['name']); ?></h3>
                    <p class="text-gray-400 mb-1">
                        <span class="font-bold">Mission:</span> 
                        <a href="info_mission.php?id=<?php echo $asset['mission_id']; ?>" class="text-blue-400 hover:underline">
                            <?php echo htmlspecialchars($asset['mission_name']); ?>
                        </a>
                    </p>
                    <p class="text-gray-400">
                        <span class="font-bold">Créateur:</span> 
                        <a href="info_user.php?id=<?php echo htmlspecialchars($asset['user_id']); ?>" class="text-blue-400 hover:underline">
                            <?php echo htmlspecialchars($asset['user_name']); ?>
                        </a>
                    </p>
                    <p class="text-gray-400 text-sm mt-2">
                        <?php echo date('d/m/Y', strtotime($asset['created_at'])); ?>
                    </p>
                </div>
                <?php if ($isChef): ?>
                    <div class="p-4 border-t border-gray-700">
                        <form method="POST" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cet asset ?');">
                            <input type="hidden" name="asset_id" value="<?php echo $asset['id']; ?>">
                            <button type="submit" 
                                    name="delete_asset"
                                    class="w-full px-4 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600 transition-colors">
                                Supprimer
                            </button>
                        </form>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<?php require_once __DIR__ . "/INCLUDE/footer.php"; ?>
