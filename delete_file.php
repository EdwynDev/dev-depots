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
if (isset($_GET['path'])) {
    $filePath = urldecode($_GET['path']);
    $uploadsDir = __DIR__ . '/uploads';

    $filePath = str_replace('\\', '/', $filePath);
    $uploadsDir = str_replace('\\', '/', $uploadsDir);
    echo "Chemin du fichier à supprimer : " . $filePath . "<br>";
    echo "Répertoire uploads : " . $uploadsDir . "<br>";
    if (strpos($filePath, $uploadsDir) === 0 && file_exists($filePath)) {
        // Supprimer le fichier
        if (unlink($filePath)) {
            // Supprimer la ligne correspondante dans la base de données
            $deleted = $mainController->deleteFileFromDatabase($filePath);
            if ($deleted) {
                header("Location: files.php");
                exit;
            }
        }
    }
}
