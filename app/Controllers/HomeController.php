<?php

require_once 'BaseController.php';

class HomeController extends BaseController
{
    public function index()
    {
        if ($this->user) {
            $this->redirect('/dashboard');
            return;
        }

        $client_id = DISCORD_CLIENT_ID;
        $redirect_uri = 'https://devdepots.neopolyworks.fr/';
        $scope = 'identify';

        if (isset($_GET['code'])) {
            $this->handleDiscordCallback();
            return;
        }

        $discordUrl = "https://discord.com/api/oauth2/authorize?client_id={$client_id}&redirect_uri=" . urlencode($redirect_uri) . "&response_type=code&scope={$scope}";

        $this->render('auth/login', [
            'title' => 'Connexion',
            'discordUrl' => $discordUrl
        ]);
    }

    private function handleDiscordCallback()
    {
        $code = $_GET['code'];
        $client_id = DISCORD_CLIENT_ID;
        $client_secret = DISCORD_CLIENT_SECRET;
        $redirect_uri = 'https://devdepots.neopolyworks.fr/';
        $scope = 'identify';

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
        curl_close($curl);

        $decoded_response = json_decode($response, true);
        $token = $decoded_response['access_token'] ?? null;

        if ($token) {
            $curl = curl_init('https://discord.com/api/users/@me');
            curl_setopt($curl, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $token]);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            $user_response = curl_exec($curl);
            curl_close($curl);

            $user_info = json_decode($user_response, true);

            if ($user_info && isset($user_info['id']) && $this->mainController->checkIfInDatabase($user_info['id'])) {
                $avatarUrl = $user_info['avatar']
                    ? "https://cdn.discordapp.com/avatars/{$user_info['id']}/{$user_info['avatar']}.png"
                    : 'https://neopolyworks.fr/INCLUDE/pp.png';

                $bannerUrl = isset($user_info['banner'])
                    ? "https://cdn.discordapp.com/banners/{$user_info['id']}/{$user_info['banner']}"
                    : 'https://neopolyworks.fr/INCLUDE/banner.png';

                $_SESSION['discord_user'] = [
                    'id' => $user_info['id'],
                    'username' => $user_info['username'],
                    'avatar' => $avatarUrl,
                    'banner_url' => $bannerUrl
                ];
                
                $this->redirect('/dashboard');
                return;
            }
        }

        $this->redirect('/');
    }
}