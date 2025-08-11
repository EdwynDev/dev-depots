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
$user = $mainController->checkIfInDatabase($userId);

if (!$user) {
    session_destroy();
    exit;
}

$isChef = $mainController->checkIfIsChefs($userId);
$domainId = $user['domaine_id_secondary'];
$domainName = $mainController->getDomainName($domainId);

// Configuration de la pagination avec 8 missions par page
$itemsPerPage = 6;
$currentPage = isset($_GET['page']) ? (int)$_GET['page'] : 1;

// Récupération et regroupement des missions par nom
$allMissions = $mainController->getMissionsByDomain($domainId);
$groupedMissions = [];
foreach ($allMissions as $mission) {
    $name = $mission['name'];
    if (!isset($groupedMissions[$name])) {
        $groupedMissions[$name] = [
            'info' => $mission,
            'count' => 1,
            'ids' => [$mission['id']],
            'available_count' => $mission['status'] === 'available' ? 1 : 0
        ];
    } else {
        $groupedMissions[$name]['count']++;
        $groupedMissions[$name]['ids'][] = $mission['id'];
        if ($mission['status'] === 'available') {
            $groupedMissions[$name]['available_count']++;
        }
    }
}

// Filter missions by difficulty
$difficultyFilter = isset($_GET['difficulty_filter']) ? $_GET['difficulty_filter'] : '';
if ($difficultyFilter) {
    $groupedMissions = array_filter($groupedMissions, function ($mission) use ($difficultyFilter) {
        return $mission['info']['difficulty'] === $difficultyFilter;
    });
}

// Gestion de la suppression d'une mission
if ($isChef && isset($_POST['delete_mission'])) {
    $missionIds = $_POST['delete_mission'];
    $deleted = $mainController->deleteMission($missionIds);
    header("Location: missionSecondary.php");
    exit;
}

// Gestion du changement de domaine secondaire
if ($isChef && isset($_POST['update_secondary_domain'])) {
    $newDomainId = (int)$_POST['new_domain_id'];
    if ($mainController->updateDomaineSecondaryForChef($userId, $newDomainId)) {
        header("Location: missionSecondary.php");
        exit;
    }
}

// Calcul de la pagination
$totalMissions = count($groupedMissions);
$totalPages = ceil($totalMissions / $itemsPerPage);
$offset = ($currentPage - 1) * $itemsPerPage;
$missions = array_slice($groupedMissions, $offset, $itemsPerPage, true);

require_once __DIR__ . "/INCLUDE/header.php";
require_once __DIR__ . '/Parsedown.php';

use Markdown\Parsedown;

$parsedown = new Parsedown();
?>

<style>
    .markdown-content {
        max-height: 100px;
    }

    .markdown-content p {
        margin-bottom: 1rem !important;
        line-height: 1.5 !important;
    }

    .markdown-content strong {
        font-weight: 700 !important;
        color: inherit !important;
    }

    .markdown-content em {
        font-style: italic !important;
    }

    .markdown-content h1 {
        font-size: 2.25rem !important;
        font-weight: 800 !important;
        margin-bottom: 1rem !important;
        margin-top: 2rem !important;
        color: inherit !important;
        border-bottom: 1px solid rgba(255, 255, 255, 0.1) !important;
        padding-bottom: 0.5rem !important;
    }

    .markdown-content h2 {
        font-size: 1.75rem !important;
        font-weight: 700 !important;
        margin-bottom: 0.875rem !important;
        margin-top: 1.75rem !important;
        color: inherit !important;
        border-bottom: 1px solid rgba(255, 255, 255, 0.1) !important;
        padding-bottom: 0.25rem !important;
    }

    .markdown-content h3 {
        font-size: 1.5rem !important;
        font-weight: 700 !important;
        margin-bottom: 0.75rem !important;
        margin-top: 1.5rem !important;
        color: inherit !important;
    }

    .markdown-content h4 {
        font-size: 1.25rem !important;
        font-weight: 600 !important;
        margin-bottom: 0.5rem !important;
        margin-top: 1.25rem !important;
        color: inherit !important;
    }

    .markdown-content ul,
    .markdown-content ol {
        padding-left: 1.5rem !important;
        margin-bottom: 1rem !important;
    }

    .markdown-content ul {
        list-style-type: disc !important;
    }

    .markdown-content ol {
        list-style-type: decimal !important;
    }

    .markdown-content li {
        margin-bottom: 0.5rem !important;
        line-height: 1.5 !important;
    }

    .markdown-content hr {
        border-top: 1px solid currentColor !important;
        margin: 1.5rem 0 !important;
        opacity: 0.3 !important;
    }
</style>

<?php if (!$domainName): ?>
    <p class="text-center text-gray-700">Vous n'avez pas de domaine secondaire !</p>
<?php else: ?>
    <h2 class="text-2xl font-bold mb-4">📇 Missions <?php echo htmlspecialchars($domainName); ?></h2>

    <?php if ($isChef) : ?>
        <form method="POST" action="" class="mb-6">
            <label for="update_secondary_domain" class="block text-gray-300 mb-2">Changer de domaine secondaire (Chefs only) :</label>
            <select name="new_domain_id" id="update_secondary_domain" class="px-4 py-2 bg-gray-700 text-white rounded-lg">
                <option value="1" <?php if ($domainName === 'Graphisme') echo 'selected'; ?>>Graphisme</option>
                <option value="2" <?php if ($domainName === 'Animation') echo 'selected'; ?>>Animation</option>
                <option value="3" <?php if ($domainName === 'Développement') echo 'selected'; ?>>Développement</option>
                <option value="4" <?php if ($domainName === 'Modélisation 3D') echo 'selected'; ?>>Modélisation 3D</option>
                <option value="5" <?php if ($domainName === 'Sound Désigner') echo 'selected'; ?>>Sound Désigner</option>
            </select>
            <input type="hidden" name="update_secondary_domain" value="1">
            <button type="submit" class="mt-2 px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600">Changer</button>
        </form>
    <?php endif; ?>
    <hr class="my-4">
    <form method="GET" action="" class="mb-6">
        <label for="difficulty_filter" class="block text-gray-300 mb-2">Filtrer par difficulté :</label>
        <select name="difficulty_filter" id="difficulty_filter" onchange="this.form.submit()" class="px-4 py-2 bg-gray-700 text-white rounded-lg">
            <option value="">Toutes</option>
            <option value="facile" <?php if ($difficultyFilter === 'facile') echo 'selected'; ?>>Facile</option>
            <option value="normal" <?php if ($difficultyFilter === 'normal') echo 'selected'; ?>>Normal</option>
            <option value="difficile" <?php if ($difficultyFilter === 'difficile') echo 'selected'; ?>>Difficile</option>
        </select>
    </form>
    <?php if (empty($groupedMissions)): ?>
        <p class="text-center text-gray-700">Aucune mission dans le domaine <?php echo htmlspecialchars($domainName); ?> disponible !</p>
    <?php else: ?>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <?php foreach ($missions as $name => $missionGroup): ?>
                <div class="bg-gradient-to-br from-gray-700 to-<?php echo $missionGroup['info']['difficulty'] === 'facile' ? 'green-500' : ($missionGroup['info']['difficulty'] === 'normal' ? 'yellow-500' : 'red-500'); ?> rounded-lg p-4 relative">
                    <div class="absolute -top-3 -right-3 bg-green-500 text-white px-3 py-1 rounded-full font-bold">
                        <?php echo $missionGroup['available_count'] . '/' . $missionGroup['count']; ?>
                    </div>
                    <h3 class="text-xl font-bold mb-2"> <?php echo htmlspecialchars($name); ?> </h3>
                    <div class="markdown-content bg-gray-600 rounded-lg p-2 text-white mb-4 overflow-auto">
                        <?php
                        $shortDesc = substr($missionGroup['info']['description'], 0, 150);
                        if (strlen($missionGroup['info']['description']) > 150) {
                            $shortDesc .= '...';
                        }
                        echo $parsedown->text($shortDesc);
                        ?>
                    </div>
                    <?php
                    // Sélectionner un ID aléatoire parmi les missions disponibles
                    $availableIds = array_filter($missionGroup['ids'], function ($id) use ($mainController) {
                        $mission = $mainController->getMissionById($id);
                        return $mission['status'] === 'available';
                    });
                    $randomId = !empty($availableIds) ? $availableIds[array_rand($availableIds)] : $missionGroup['ids'][0];
                    ?>
                    <a href="info_mission.php?id=<?php echo $randomId; ?>" class="block w-full bg-green-500 text-white text-center py-2 rounded-md hover:bg-green-600 transition-colors">Voir plus</a>
                    <hr class="my-4">
                    <?php if ($missionGroup['available_count'] === 0) : ?>
                        <p class="bg-red-500 text-white text-center py-1 rounded-md">Aucun exemplaire disponible</p>
                    <?php else : ?>
                        <p class="bg-blue-500 text-white text-center py-1 rounded-md"><?php echo $missionGroup['available_count']; ?> exemplaire<?php echo $missionGroup['available_count'] > 1 ? 's' : ''; ?> disponible<?php echo $missionGroup['available_count'] > 1 ? 's' : ''; ?></p>
                    <?php endif; ?>
                    <?php if ($isChef && ($domainName == $mainController->getDomainName($mainController->getUserById($userId)['domaine_id']))): ?>
                        <form class="mt-4" method="post" action="" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cette mission ?');">
                            <?php foreach ($missionGroup['ids'] as $id): ?>
                                <input type="hidden" name="delete_mission[]" value="<?php echo $id; ?>">
                            <?php endforeach; ?>
                            <button type="submit" class="w-full bg-red-500 text-white py-2 rounded-md hover:bg-red-600 transition-colors">Supprimer tous les exemplaires</button>
                        </form>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="flex justify-center gap-2 my-6">
            <?php if ($currentPage > 1): ?>
                <a href="?page=<?php echo $currentPage - 1; ?>&difficulty_filter=<?php echo $difficultyFilter; ?>" class="px-4 py-2 border rounded-md hover:bg-gray-100">«</a>
            <?php endif; ?>

            <?php
            $totalButtons = 5;
            $halfButtons = floor($totalButtons / 2);

            if ($totalPages <= $totalButtons) {
                $start = 1;
                $end = $totalPages;
            } else {
                $start = max(1, min($currentPage - $halfButtons, $totalPages - $totalButtons + 1));
                $end = min($totalPages, $start + $totalButtons - 1);
            }

            for ($i = $start; $i <= $end; $i++): ?>
                <?php if ($i == $currentPage): ?>
                    <span class="px-4 py-2 bg-green-500 text-white rounded-md"><?php echo $i; ?></span>
                <?php else: ?>
                    <a href="?page=<?php echo $i; ?>&difficulty_filter=<?php echo $difficultyFilter; ?>#pagi" class="px-4 py-2 border rounded-md hover:bg-gray-100"><?php echo $i; ?></a>
            <?php endif;
            endfor; ?>

            <?php if ($currentPage < $totalPages): ?>
                <a href="?page=<?php echo $currentPage + 1; ?>&difficulty_filter=<?php echo $difficultyFilter; ?>#pagi" class="px-4 py-2 border rounded-md hover:bg-gray-100">»</a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
<?php endif; ?>

<?php require_once __DIR__ . "/INCLUDE/footer.php"; ?>