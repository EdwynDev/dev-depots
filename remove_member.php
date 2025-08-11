<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

if (!isset($_SESSION['discord_user'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Non autorisé']);
    exit;
}

require_once __DIR__ . '/CONTROLLERS/MainController.php';
require 'config.php';

use Controllers\MainController;

$mainController = new MainController();

$userId = $_SESSION['discord_user']['id'];
$isChef = $mainController->checkIfIsChefs($userId);
$isRef = $mainController->checkIfIsRef($userId);

if (!$isChef && !$isRef) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Non autorisé']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['userIds']) || !is_array($data['userIds'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Données manquantes ou invalides']);
    exit;
}

try {
    $result = $mainController->removeMember($data['userIds']);
    echo json_encode([
        'success' => $result,
        'message' => $result ? 'Membres supprimés avec succès' : 'Échec de la suppression'
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erreur lors de la suppression: ' . $e->getMessage()
    ]);
}
