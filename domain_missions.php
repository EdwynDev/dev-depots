<?php
if (session_status() == PHP_SESSION_NONE) {
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

$domainId = isset($_GET['id']) ? intval($_GET['id']) : $user['domaine_id'];
$domainName = $mainController->getDomainName($domainId);
$missionDetails = $mainController->getDetailedUploadInfo(null, $domainId);
$allMissions = $mainController->getMissionsByDomainAdmin($domainId);

$groupedMissions = [];
foreach ($missionDetails as $detail) {
    $missionName = $detail['mission_name'];
    if (!isset($groupedMissions[$missionName])) {
        $groupedMissions[$missionName] = [
            'description' => $detail['mission_description'],
            'difficulty' => $detail['mission_difficulty'],
            'created_at' => $detail['mission_created_at'],
            'completed_by' => [],
            'in_progress' => []
        ];
    }

    if (
        !empty($detail['uploader_name']) && $detail['mission_status'] === 'completed' &&
        !in_array($detail['uploader_name'], $groupedMissions[$missionName]['completed_by'])
    ) {
        $groupedMissions[$missionName]['completed_by'][] = $detail['uploader_name'];
    }
}

foreach ($allMissions as $mission) {
    if ($mission['status'] === 'assigned' && isset($groupedMissions[$mission['name']])) {
        $assigneeName = $mainController->getUsername($mission['assignee_id']);
        if (!in_array($assigneeName, $groupedMissions[$mission['name']]['in_progress'])) {
            $groupedMissions[$mission['name']]['in_progress'][] = $assigneeName;
        }
    }
}

uasort($groupedMissions, function ($a, $b) {
    return strtotime($b['created_at']) - strtotime($a['created_at']);
});

require_once __DIR__ . "/INCLUDE/header.php";
?>

<div class="container mx-auto px-4 py-8">
    <h1 class="text-2xl font-bold mb-6">Missions du domaine <?php echo htmlspecialchars($domainName); ?></h1>

    <div class="grid grid-cols-1 gap-6">
        <?php foreach ($groupedMissions as $missionName => $missionInfo): ?>
            <div class="bg-gray-800 rounded-lg shadow-lg p-6">
                <h2 class="text-xl font-semibold mb-4"><?php echo htmlspecialchars($missionName); ?></h2>

                <p class="text-sm text-gray-400 mb-3">Créée le: <?php echo date('d/m/Y H:i', strtotime($missionInfo['created_at'])); ?></p>

                <div class="mb-4">
                    <h3 class="font-semibold text-green-400 mb-2">Complétée par:</h3>
                    <?php if (empty($missionInfo['completed_by'])): ?>
                        <p class="text-gray-400">Personne n'a encore complété cette mission</p>
                    <?php else: ?>
                        <div class="flex flex-wrap gap-2">
                            <?php foreach ($missionInfo['completed_by'] as $user): ?>
                                <div class="bg-gray-700 rounded-lg px-3 py-2 text-gray-300">
                                    <a href="info_user.php?username=<?php echo htmlspecialchars($user); ?>" class="text-blue-400 hover:underline"><?php echo htmlspecialchars($user); ?></a>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div>
                    <h3 class="font-semibold text-yellow-400 mb-2">En cours par:</h3>
                    <?php if (empty($missionInfo['in_progress'])): ?>
                        <p class="text-gray-400">Personne ne travaille sur cette mission</p>
                    <?php else: ?>
                        <div class="flex flex-wrap gap-2">
                            <?php foreach ($missionInfo['in_progress'] as $user): ?>
                                <div class="bg-gray-700 rounded-lg px-3 py-2 text-gray-300">
                                    <a href="info_user.php?username=<?php echo htmlspecialchars($user); ?>" class="text-blue-400 hover:underline"><?php echo htmlspecialchars($user); ?></a>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="mt-4 pt-4 border-t border-gray-600">
                    <span class="inline-block bg-blue-500 text-white px-3 py-1 rounded-full text-sm">
                        Difficulté: <?php echo htmlspecialchars($missionInfo['difficulty']); ?>
                    </span>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<?php require_once __DIR__ . "/INCLUDE/footer.php"; ?>