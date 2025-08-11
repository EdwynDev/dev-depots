<?php

if (session_status() == PHP_SESSION_NONE) {
    session_set_cookie_params(30 * 24 * 60 * 60);
    session_start();
}


require_once __DIR__ . '/CONTROLLERS/MainController.php';
require 'config.php';

use Controllers\MainController;

$mainController = new MainController();

if (isset($_SESSION['discord_user'])) {
    $userId = $_SESSION['discord_user']['id'];
    $isChef = $mainController->checkIfIsChefs($userId);
}

$client_id = DISCORD_CLIENT_ID;
$client_secret = DISCORD_CLIENT_SECRET;
$redirect_uri = 'https://depots.neopolyworks.fr/index.php';
$scope = 'identify';

function redirectToDiscord($client_id, $redirect_uri, $scope)
{
    return "https://discord.com/api/oauth2/authorize?client_id={$client_id}&redirect_uri=" . urlencode($redirect_uri) . "&response_type=code&scope={$scope}";
}

if (isset($_GET['code'])) {
    $code = $_GET['code'];

    $data = [
        'client_id' => $client_id,
        'client_secret' => $client_secret,
        'grant_type' => 'authorization_code',
        'code' => $code,
        'redirect_uri' => $redirect_uri,
        'scope' => $scope
    ];

    $curl = curl_init('https://discord.com/api/oauth2/token');
    curl_setopt($curl, CURLOPT_POST, true);
    curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);
    $response = curl_exec($curl);

    if (curl_errno($curl)) {
        curl_close($curl);
        exit;
    }

    curl_close($curl);

    $decoded_response = json_decode($response, true);
    $token = $decoded_response['access_token'] ?? null;

    if ($token) {
        $curl = curl_init('https://discord.com/api/users/@me');
        curl_setopt($curl, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $token]);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        $user_response = curl_exec($curl);

        if (curl_errno($curl)) {
            curl_close($curl);
            exit;
        }

        curl_close($curl);

        $user_info = json_decode($user_response, true);

        if ($user_info && isset($user_info['id']) && $mainController->checkIfInDatabase($user_info['id'])) {
            // Construire l'URL de l'avatar
            $avatarUrl = $user_info['avatar']
                ? "https://cdn.discordapp.com/avatars/{$user_info['id']}/{$user_info['avatar']}.png"
                : 'https://neopolyworks.fr/INCLUDE/pp.png';

            // Construire l'URL de la bannière si elle existe
            $bannerUrl = isset($user_info['banner'])
                ? "https://cdn.discordapp.com/banners/{$user_info['id']}/{$user_info['banner']}"
                : 'https://neopolyworks.fr/INCLUDE/banner.png';

            $_SESSION['discord_user'] = [
                'id' => $user_info['id'],
                'username' => $user_info['username'],
                'avatar' => $avatarUrl,
                'banner_url' => $bannerUrl
            ];
            header("Location: https://depots.neopolyworks.fr");
            exit;
        } else {
            header("Location: https://depots.neopolyworks.fr");
        }
    }
}
require_once __DIR__ . "/INCLUDE/header.php";
?>
<style>
    .login-container {
        display: flex;
        justify-content: flex-start;
        align-items: center;
        flex-direction: column;
        max-width: 42rem;
        margin: 2rem auto;
        padding: 1.5rem;
    }

    .welcome-message {
        text-align: center;
        margin-bottom: 2rem;
        font-size: 1.5rem;
        font-weight: bold;
    }

    .discord-login-btn {
        display: inline-block;
        text-align: center;
        background: #5865f2;
        color: #e4e6eb;
        padding: 15px 25px;
        border-radius: 8px;
        text-decoration: none;
        font-size: 1.2rem;
        transition: background-color 0.3s ease, box-shadow 0.3s ease;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    }

    .discord-login-btn:hover {
        background-color: #4752c4;
        box-shadow: 0 6px 8px rgba(0, 0, 0, 0.15);
    }

    .error-message {
        background-color: #fef2f2;
        border: 1px solid #fecaca;
        border-left: 5px solid rgb(254, 171, 171);
        border-radius: 0.5rem;
        padding: 1.5rem;
        margin-top: 1.5rem;
        max-width: 42rem;
        margin: 1.5rem auto;
    }

    .error-message p {
        color: #991b1b;
        margin-bottom: 1rem;
    }

    .error-message ul {
        list-style: none;
        padding: 0;
        margin: 1rem 0;
        color: #b91c1c;
    }

    .error-message li {
        margin: 0.5rem 0;
        font-weight: 500;
    }

    .error-message em {
        display: block;
        color: #991b1b;
        font-style: italic;
        margin-top: 1rem;
    }
</style>

<div class="login-container">
    <?php if (isset($_SESSION['discord_user'])): ?>
        <h2 class="welcome-message">
            Bienvenue, vous êtes connecté(e) en tant que
            <?php echo htmlspecialchars($_SESSION['discord_user']['username']); ?>
        </h2>
    <?php else: ?>

        <a class="discord-login-btn"
            href="<?php echo htmlspecialchars(redirectToDiscord($client_id, $redirect_uri, $scope)); ?>">
            SE CONNECTER AVEC DISCORD
        </a>
        <div class="error-message">
            <p>
                Si vous n'arrivez pas à vous connecter via Discord la raison est simple :
            </p>
            <ul>
                <li>- Vous n'êtes tout simplement pas enregistré ^^</li>
            </ul>
            <em>Demandez à votre chef de pôle de vous y ajouter, merci !</em>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . "/INCLUDE/footer.php"; ?>