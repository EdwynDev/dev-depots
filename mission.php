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
$domainId = $user['domaine_id'];
$domainName = $mainController->getDomainName($domainId);

$itemsPerPage = 6;
$currentPage = isset($_GET['page']) ? (int)$_GET['page'] : 1;

if ($isChef && isset($_POST['create_mission'])) {
    $name = $_POST['name'];
    $description = $_POST['description'];
    $deadline = $_POST['deadline'];
    $difficulty = $_POST['difficulty'];
    $fileType = strtolower('.zip, ' . $_POST['fileType']);
    $copies = (int)$_POST['copies'];
    $created_at = $_POST['created_at'];

    // Handle multiple reference images
    $imageUrls = [];
    if (isset($_FILES['reference_images']) && is_array($_FILES['reference_images']['error'])) {
        foreach ($_FILES['reference_images']['error'] as $key => $error) {
            if ($error === UPLOAD_ERR_OK) {
                $tmpImage = [
                    'name' => $_FILES['reference_images']['name'][$key],
                    'type' => $_FILES['reference_images']['type'][$key],
                    'tmp_name' => $_FILES['reference_images']['tmp_name'][$key],
                    'error' => $_FILES['reference_images']['error'][$key],
                    'size' => $_FILES['reference_images']['size'][$key]
                ];

                $imageUrl = $mainController->uploadImage($tmpImage);
                if ($imageUrl) {
                    $imageUrls[] = $imageUrl;
                }
            }
        }
    }
    $referenceImagesJson = json_encode($imageUrls);

    $attachedFilePaths = [];
    if (isset($_FILES['attached_files'])) {
        foreach ($_FILES['attached_files']['error'] as $key => $error) {
            if ($error === UPLOAD_ERR_OK) {
                $tmpFile = [
                    'name' => $_FILES['attached_files']['name'][$key],
                    'type' => $_FILES['attached_files']['type'][$key],
                    'tmp_name' => $_FILES['attached_files']['tmp_name'][$key],
                    'error' => $_FILES['attached_files']['error'][$key],
                    'size' => $_FILES['attached_files']['size'][$key]
                ];

                $uploadedFilePath = $mainController->uploadFile($tmpFile);
                if ($uploadedFilePath) {
                    $attachedFilePaths[] = $uploadedFilePath;
                }
            }
        }
    }

    $attachedFilesJson = json_encode($attachedFilePaths);

    for ($i = 0; $i < $copies; $i++) {
        $missionId = $mainController->createMission($name, $description, $deadline, $domainId, $referenceImagesJson, $difficulty, $fileType, $created_at, $attachedFilesJson);
    }
    header('Location: mission.php');
}

// Récupération des missions et regroupement par nom
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

// Récupération des missions et filtrage par difficulté
$difficultyFilter = isset($_GET['difficulty_filter']) ? $_GET['difficulty_filter'] : '';

if ($difficultyFilter) {
    $groupedMissions = array_filter($groupedMissions, function ($mission) use ($difficultyFilter) {
        return $mission['info']['difficulty'] === $difficultyFilter;
    });
}

// Gestion de la suppression des missions
if ($isChef && isset($_POST['delete_mission'])) {
    $missionIds = $_POST['delete_mission'];
    $deleted = $mainController->deleteMission($missionIds);
    header("Location: mission.php");
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
<h2 class="text-2xl font-bold mb-4">📇 Missions <?php echo htmlspecialchars($domainName); ?></h2>
<?php if ($isChef && ($domainName ==  $mainController->getDomainName($mainController->getUserById($userId)['domaine_id']))): ?>
    <button onclick="toggleFormMission()" class="toggle-form bg-gray-700 text-white p-2 rounded-lg hover:bg-gray-600 transition-colors">
        <svg width="24px" height="24px" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg">
            <g id="SVGRepo_bgCarrier" stroke-width="0"></g>
            <g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g>
            <g id="SVGRepo_iconCarrier">
                <path d="M4.001 21C5.001 24 8.001 29 16.001 29C24.001 29 27.001 24 28.001 21M31 19C31 17 27.001 7 16 7M16 7C5.001 7 1 17 1 19M16 7V3M21.1758 3.6816L20.1758 7.4106M26 5.6797L23.999 9.1427M30.1416 8.8574L27.3136 11.6844M10.8223 3.6816L11.8213 7.4106M5.999 5.6797L7.999 9.1437M1.8574 8.8574L4.6854 11.6854M16.001 12C12.688 12 10.001 14.687 10.001 18C10.001 21.313 12.688 24 16.001 24C19.314 24 22.001 21.313 22.001 18M21.2441 15.0869C20.7001 14.1089 19.8911 13.3009 18.9141 12.7569M18.001 18C18.001 16.896 17.105 16 16.001 16C14.897 16 14.001 16.896 14.001 18C14.001 19.104 14.897 20 16.001 20C17.105 20 18.001 19.104 18.001 18Z" stroke="#ffffff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path>
            </g>
        </svg>
    </button>

    <div class="create-mission-form flex-col items-center justify-center mt-4" style="display: none;">
        <h3 class="text-xl font-bold mb-4">Créer une mission</h3>
        <form method="post" action="" enctype="multipart/form-data" class="w-full max-w-lg">
            <div class="mb-4">
                <label for="name" class="block text-gray-300">Nom :</label>
                <input type="text" id="name" name="name" required class="w-full px-3 py-2 bg-gray-700 text-white rounded-lg">
            </div>

            <div class="mb-4">
                <label for="description" class="block text-gray-300">Description :</label>
                <textarea id="description" name="description" required class="w-full px-3 py-2 bg-gray-700 text-white rounded-lg"></textarea>
            </div>
            <div class="flex justify-between gap-2">
                <div class="mb-4">
                    <label for="created_at" class="block text-gray-300">Date de début :</label>
                    <input type="date" id="created_at" name="created_at" required class="w-full px-3 py-2 bg-gray-700 text-white rounded-lg">
                </div>
                <div class="mb-4">
                    <label for="difficulty" class="block text-gray-300">Difficulté :</label>
                    <select id="difficulty" name="difficulty" class="w-full px-3 py-2 bg-gray-700 text-white rounded-lg">
                        <option value="facile">Facile</option>
                        <option value="normal">Normal</option>
                        <option value="difficile">Difficile</option>
                    </select>
                </div>
                <div class="mb-4">
                    <label for="deadline" class="block text-gray-300">Date limite :</label>
                    <input type="date" id="deadline" name="deadline" required class="w-full px-3 py-2 bg-gray-700 text-white rounded-lg">
                </div>
            </div>

            <div class="mb-4">
                <label for="reference_images" class="block text-gray-300">Images de référence (plusieurs possibles) :</label>
                <input type="file" id="reference_images" name="reference_images[]" multiple accept="image/*" class="w-full px-3 py-2 bg-gray-700 text-white rounded-lg">
                <small class="text-gray-400">Vous pouvez sélectionner plusieurs images de référence</small>
            </div>

            <div class="mb-4">
                <label for="attached_files" class="block text-gray-300">Fichiers joints (plusieurs possibles) :</label>
                <input type="file" id="attached_files" name="attached_files[]" multiple class="w-full px-3 py-2 bg-gray-700 text-white rounded-lg">
                <small class="text-gray-400">Vous pouvez sélectionner plusieurs fichiers</small>
            </div>

            <div class="flex justify-between gap-2">
                <div class="mb-4">
                    <label for="copies" class="block text-gray-300">Exemplaires :</label>
                    <input type="number" id="copies" name="copies" min="1" value="1" required class="w-full px-3 py-2 bg-gray-700 text-white rounded-lg">
                </div>

                <div class="mb-4">
                    <label for="fileType" class="block text-gray-300">Type de fichier attendu :</label>
                    <input type="text" id="fileType" name="fileType" required placeholder="Ex: .blend, .fbx, .obj, .unitypackage..." class="w-full px-3 py-2 bg-gray-700 text-white rounded-lg">
                </div>
            </div>
            <button type="submit" name="create_mission" class="w-full bg-green-500 text-white py-2 rounded-lg hover:bg-green-600 transition-colors">Créer</button>
        </form>
    </div>
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

<div class="grid grid-cols-1 md:grid-cols-3 gap-6">
    <?php foreach ($missions as $name => $missionGroup): ?>
        <div class="bg-gradient-to-br from-gray-700 to-<?php echo $missionGroup['info']['difficulty'] === 'facile' ? 'green-500' : ($missionGroup['info']['difficulty'] === 'normal' ? 'yellow-500' : 'red-500'); ?> rounded-lg p-4 relative">
            <div class="absolute -top-3 -right-3 bg-green-500 text-white px-3 py-1 rounded-full font-bold">
                <?php echo $missionGroup['available_count'] . '/' . $missionGroup['count']; ?>
            </div>
            <h3 class="text-xl font-bold mb-2"><?php echo htmlspecialchars($name); ?></h3>
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
            <br>
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

<script>
    function toggleFormMission() {
        const form = document.querySelector('.create-mission-form');
        const button = document.querySelector('.toggle-form');
        const currentDisplay = form.style.display;

        if (currentDisplay === 'none') {
            form.style.display = 'flex';
            button.innerHTML = '<svg width="24px" height="24px" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"> <path d="M2.68936 6.70456C2.52619 6.32384 2.08528 6.14747 1.70456 6.31064C1.32384 6.47381 1.14747 6.91472 1.31064 7.29544L2.68936 6.70456ZM15.5872 13.3287L15.3125 12.6308L15.5872 13.3287ZM9.04145 13.7377C9.26736 13.3906 9.16904 12.926 8.82185 12.7001C8.47466 12.4742 8.01008 12.5725 7.78417 12.9197L9.04145 13.7377ZM6.37136 15.091C6.14545 15.4381 6.24377 15.9027 6.59096 16.1286C6.93815 16.3545 7.40273 16.2562 7.62864 15.909L6.37136 15.091ZM22.6894 7.29544C22.8525 6.91472 22.6762 6.47381 22.2954 6.31064C21.9147 6.14747 21.4738 6.32384 21.3106 6.70456L22.6894 7.29544ZM19 11.1288L18.4867 10.582V10.582L19 11.1288ZM19.9697 13.1592C20.2626 13.4521 20.7374 13.4521 21.0303 13.1592C21.3232 12.8663 21.3232 12.3914 21.0303 12.0985L19.9697 13.1592ZM11.25 16.5C11.25 16.9142 11.5858 17.25 12 17.25C12.4142 17.25 12.75 16.9142 12.75 16.5H11.25ZM16.3714 15.909C16.5973 16.2562 17.0619 16.3545 17.409 16.1286C17.7562 15.9027 17.8545 15.4381 17.6286 15.091L16.3714 15.909ZM5.53033 11.6592C5.82322 11.3663 5.82322 10.8914 5.53033 10.5985C5.23744 10.3056 4.76256 10.3056 4.46967 10.5985L5.53033 11.6592ZM2.96967 12.0985C2.67678 12.3914 2.67678 12.8663 2.96967 13.1592C3.26256 13.4521 3.73744 13.4521 4.03033 13.1592L2.96967 12.0985ZM12 13.25C8.77611 13.25 6.46133 11.6446 4.9246 9.98966C4.15645 9.16243 3.59325 8.33284 3.22259 7.71014C3.03769 7.3995 2.90187 7.14232 2.8134 6.96537C2.76919 6.87696 2.73689 6.80875 2.71627 6.76411C2.70597 6.7418 2.69859 6.7254 2.69411 6.71533C2.69187 6.7103 2.69036 6.70684 2.68957 6.70503C2.68917 6.70413 2.68896 6.70363 2.68892 6.70355C2.68891 6.70351 2.68893 6.70357 2.68901 6.70374C2.68904 6.70382 2.68913 6.70403 2.68915 6.70407C2.68925 6.7043 2.68936 6.70456 2 7C1.31064 7.29544 1.31077 7.29575 1.31092 7.29609C1.31098 7.29624 1.31114 7.2966 1.31127 7.2969C1.31152 7.29749 1.31183 7.2982 1.31218 7.299C1.31287 7.30062 1.31376 7.30266 1.31483 7.30512C1.31698 7.31003 1.31988 7.31662 1.32353 7.32483C1.33083 7.34125 1.34115 7.36415 1.35453 7.39311C1.38127 7.45102 1.42026 7.5332 1.47176 7.63619C1.57469 7.84206 1.72794 8.13175 1.93366 8.47736C2.34425 9.16716 2.96855 10.0876 3.8254 11.0103C5.53867 12.8554 8.22389 14.75 12 14.75V13.25ZM15.3125 12.6308C14.3421 13.0128 13.2417 13.25 12 13.25V14.75C13.4382 14.75 14.7246 14.4742 15.8619 14.0266L15.3125 12.6308ZM7.78417 12.9197L6.37136 15.091L7.62864 15.909L9.04145 13.7377L7.78417 12.9197ZM22 7C21.3106 6.70456 21.3107 6.70441 21.3108 6.70427C21.3108 6.70423 21.3108 6.7041 21.3109 6.70402C21.3109 6.70388 21.311 6.70376 21.311 6.70368C21.3111 6.70352 21.3111 6.70349 21.3111 6.7036C21.311 6.7038 21.3107 6.70452 21.3101 6.70576C21.309 6.70823 21.307 6.71275 21.3041 6.71924C21.2983 6.73223 21.2889 6.75309 21.2758 6.78125C21.2495 6.83757 21.2086 6.92295 21.1526 7.03267C21.0406 7.25227 20.869 7.56831 20.6354 7.9432C20.1669 8.69516 19.4563 9.67197 18.4867 10.582L19.5133 11.6757C20.6023 10.6535 21.3917 9.56587 21.9085 8.73646C22.1676 8.32068 22.36 7.9668 22.4889 7.71415C22.5533 7.58775 22.602 7.48643 22.6353 7.41507C22.6519 7.37939 22.6647 7.35118 22.6737 7.33104C22.6782 7.32097 22.6818 7.31292 22.6844 7.30696C22.6857 7.30398 22.6867 7.30153 22.6876 7.2996C22.688 7.29864 22.6883 7.29781 22.6886 7.29712C22.6888 7.29677 22.6889 7.29646 22.689 7.29618C22.6891 7.29604 22.6892 7.29585 22.6892 7.29578C22.6893 7.29561 22.6894 7.29544 22 7ZM18.4867 10.582C17.6277 11.3882 16.5739 12.1343 15.3125 12.6308L15.8619 14.0266C17.3355 13.4466 18.5466 12.583 19.5133 11.6757L18.4867 10.582ZM18.4697 11.6592L19.9697 13.1592L21.0303 12.0985L19.5303 10.5985L18.4697 11.6592ZM11.25 14V16.5H12.75V14H11.25ZM14.9586 13.7377L16.3714 15.909L17.6286 15.091L16.2158 12.9197L14.9586 13.7377ZM4.46967 10.5985L2.96967 12.0985L4.03033 13.1592L5.53033 11.6592L4.46967 10.5985Z" fill="#ffffff"></path> </g></svg>';
        } else {
            form.style.display = 'none';
            button.innerHTML = '<svg width="24px" height="24px" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"> <path d="M4.001 21C5.001 24 8.001 29 16.001 29C24.001 29 27.001 24 28.001 21M31 19C31 17 27.001 7 16 7M16 7C5.001 7 1 17 1 19M16 7V3M21.1758 3.6816L20.1758 7.4106M26 5.6797L23.999 9.1427M30.1416 8.8574L27.3136 11.6844M10.8223 3.6816L11.8213 7.4106M5.999 5.6797L7.999 9.1437M1.8574 8.8574L4.6854 11.6854M16.001 12C12.688 12 10.001 14.687 10.001 18C10.001 21.313 12.688 24 16.001 24C19.314 24 22.001 21.313 22.001 18M21.2441 15.0869C20.7001 14.1089 19.8911 13.3009 18.9141 12.7569M18.001 18C18.001 16.896 17.105 16 16.001 16C14.897 16 14.001 16.896 14.001 18C14.001 19.104 14.897 20 16.001 20C17.105 20 18.001 19.104 18.001 18Z" stroke="#ffffff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path> </g></svg>';
        }
    }
</script>

<?php require_once __DIR__ . "/INCLUDE/footer.php"; ?>