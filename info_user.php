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


if (isset($_GET['id'])) {
    $userId = $_GET['id'];
    $user = $mainController->getUserById($userId);
} elseif (isset($_GET['username'])) {
    $username = $_GET['username'];
    $user = $mainController->getUserByUsername($username);
} else {
    echo "Aucun utilisateur spécifié.";
    exit;
}

if (!$user) {
    echo "Utilisateur non trouvé.";
    exit;
}

$userDomain = $mainController->getDomainName($user['domaine_id']);
$userChefs = $mainController->getChefsForUser($user['userId']);
$userMissions = $mainController->getMissionsForUser($user['userId']);
$userUploads = $mainController->getUploadsForUser($user['userId']);
$activeMissions = $mainController->getActiveMissions($user['userId']);

// Configuration de l'appel API Discord
$discordApiUrl = "https://discord.com/api/v10/users/" . $user['userId'];
$discordHeaders = [
    "Authorization: Bot " . DISCORD_BOT_TOKEN,
    "Content-Type: application/json"
];

$ch = curl_init($discordApiUrl);
curl_setopt_array($ch, [
    CURLOPT_HTTPHEADER => $discordHeaders,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_SSL_VERIFYPEER => true,
    CURLOPT_SSL_VERIFYHOST => 2
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

if (curl_errno($ch)) {
    error_log('Curl error: ' . curl_error($ch));
}

curl_close($ch);

$discordUser = json_decode($response, true);

if ($httpCode === 200 && $discordUser) {
    if (isset($discordUser['avatar']) && $discordUser['avatar']) {
        $avatarHash = $discordUser['avatar'];
        $avatarExtension = strpos($avatarHash, 'a_') === 0 ? '.gif' : '.png';
        $userAvatar = "https://cdn.discordapp.com/avatars/{$user['userId']}/{$avatarHash}{$avatarExtension}?size=256";
    } else {
        $discriminator = $discordUser['discriminator'] ?? '0';
        $defaultAvatarNumber = $discriminator ? intval($discriminator) % 5 : rand(0, 4);
        $userAvatar = "https://cdn.discordapp.com/embed/avatars/{$defaultAvatarNumber}.png";
    }

    if (isset($discordUser['banner']) && $discordUser['banner']) {
        $bannerHash = $discordUser['banner'];
        $bannerExtension = strpos($bannerHash, 'a_') === 0 ? '.gif' : '.png';
        $userBanner = "https://cdn.discordapp.com/banners/{$user['userId']}/{$bannerHash}{$bannerExtension}?size=600";
    } else {
        if (isset($discordUser['banner_color'])) {
            $userBanner = null;
            $bannerColor = $discordUser['banner_color'];
        } else {
            $userBanner = "https://neopolyworks.fr/INCLUDE/banner.png";
        }
    }
} else {
    $userAvatar = "https://neopolyworks.fr/INCLUDE/pp.png";
    $userBanner = "https://neopolyworks.fr/INCLUDE/banner.png";
}
require_once __DIR__ . "/INCLUDE/header.php";
?>

<a href="javascript:history.back()" class="inline-block px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition-colors mb-4">Retour</a>

<div class="max-w-3xl mx-auto bg-gray-800 p-6 rounded-lg shadow-lg text-white">
    <small class="text-gray-400"><em>(<?php echo htmlspecialchars($user['userId']); ?>)</em></small>
    <div class="flex flex-col items-center">
        <div class="w-32 h-32 rounded-full overflow-hidden mb-4">
            <img src="<?php echo $userAvatar; ?>" alt="Avatar de <?php echo htmlspecialchars($user['username']); ?>" class="w-full h-full object-cover">
        </div>
        <div class="text-center">
            <h2 class="text-2xl font-bold mb-2"><?php echo htmlspecialchars($user['username']); ?></h2>
            <p class="bg-gray-700 px-3 py-1 rounded-lg"><strong><?php echo htmlspecialchars($userDomain); ?></strong></p>
            <p class="mt-2"><strong>✅ Missions complétées:</strong> <?php echo count($userMissions); ?> | <strong>🚀 Uploads:</strong> <?php echo count($userUploads); ?></p>

            <?php if (!empty($activeMissions)): ?>
                <div class="max-w-2xl w-full bg-gray-800 rounded-lg shadow-lg p-6 text-left mb-6">
                    <div class="space-y-2">
                        <?php foreach ($activeMissions as $mission): ?>
                            <div class="flex justify-between items-center bg-gray-700 p-3 rounded-lg">
                                <a href="info_mission.php?id=<?php echo htmlspecialchars($mission['id']); ?>" class="flex-1 text-blue hover:underline">
                                    <div class="font-semibold"><?php echo htmlspecialchars($mission['name']); ?></div>
                                </a>
                                <div class="text-sm <?php echo $mission['status'] === 'completed' ? 'text-green-400' : 'text-yellow-400'; ?>">
                                    <?php echo $mission['status'] === 'completed' ? '✅' : '⏳'; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php else: ?>
                <hr class="my-4">
                <p>Aucune Mission en cours</p>
            <?php endif; ?>
        </div>
    </div>

    <?php if (isset($bannerColor)): ?>
        <div class="w-full h-48 rounded-lg mb-4" style="background-color: <?php echo htmlspecialchars($bannerColor); ?>"></div>
    <?php elseif (!empty($userBanner)): ?>
        <div class="w-full h-48 rounded-lg overflow-hidden mb-4">
            <img src="<?php echo htmlspecialchars($userBanner); ?>" alt="Bannière de <?php echo htmlspecialchars($user['username']); ?>" class="w-full h-full object-cover">
        </div>
    <?php endif; ?>
</div>

<div class="max-w-3xl mx-auto bg-gray-800 p-6 rounded-lg shadow-lg text-white mt-6">
    <h3 class="text-xl font-bold mb-4">Historique des missions</h3>
    <div class="space-y-2">
        <?php foreach ($userMissions as $mission): ?>
            <div class="flex justify-between items-center bg-gray-700 p-3 rounded-lg">
                <div class="font-semibold"><?php echo htmlspecialchars($mission['name']); ?></div>
                <div class="text-sm <?php echo $mission['status'] === 'completed' ? 'text-green-400' : 'text-yellow-400'; ?>">
                    <?php echo $mission['status'] === 'completed' ? '✅' : '⏳'; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<?php require_once __DIR__ . "/INCLUDE/footer.php"; ?>