<?php
header('Content-Type: application/json');
require_once __DIR__ . '/CONTROLLERS/MainController.php';
require 'config.php';

use Controllers\MainController;


if (session_status() == PHP_SESSION_NONE) {
    session_set_cookie_params(30 * 24 * 60 * 60);
    session_start();
}

if (!isset($_SESSION['discord_user'])) {
    echo json_encode(['success' => false, 'error' => 'Non autorisé']);
    exit;
}

if (isset($_POST['comment_id']) && isset($_POST['reaction_type'])) {
    $mainController = new MainController();
    $userId = $_SESSION['discord_user']['id'];
    $commentId = (int)$_POST['comment_id'];
    $reactionType = $_POST['reaction_type'];
    
    $mission = $mainController->getMissionByCommentId($commentId);
    if ($mission && $mission['assignee_id'] === $userId) {
        $result = $mainController->addReaction($commentId, $userId, $reactionType);
        
        if ($result) {
            try {
                $webhookUrl = 'https://discord.com/api/webhooks/1333808101786128548/IZtPysLVWmP_zxa4fclFG0-1FBzuc64Ukk9RIppNO0A3RRpkWMyWdmRAAgE4zx9lxiva';
                $reactorName = $_SESSION['discord_user']['username'] ?? 'L\'assigné';
                
                $reactions = [
                    'like' => '👍',
                    'heart' => '❤️'
                ];
                
                $messageContent = sprintf(
                    "> L'assigné de la mission a réagi à un commentaire !\n" .
                    "**Réaction:** %s\n" .
                    "**De:** %s\n" .
                    "**Mission:** %s\n" .
                    "**Lien:** https://depots.neopolyworks.fr/info_mission.php?id=%d#comment-%d",
                    $reactions[$reactionType] ?? $reactionType,
                    $reactorName,
                    $mission['name'],
                    $mission['id'],
                    $commentId
                );

                $webhookData = [
                    'content' => $messageContent
                ];

                $ch = curl_init($webhookUrl);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($webhookData));
                curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                $response = curl_exec($ch);
                curl_close($ch);
            } catch (Exception $e) {
                error_log("Erreur lors de l'envoi au webhook: " . $e->getMessage());
            }
        }
        
        echo json_encode(['success' => $result]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Non autorisé']);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Paramètres manquants']);
}