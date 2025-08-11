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

// Vérification de la connexion utilisateur
$userId = $_SESSION['discord_user']['id'];
$user = $mainController->checkIfInDatabase($userId);

if (!$user) {
    session_destroy();
    header("Location: index.php");
    exit;
}

// Vérification des droits chef
$isChef = $mainController->checkIfIsChefs($userId);
if (!$isChef) {
    header("Location: mission.php");
    exit;
}

// Vérification de l'ID de mission dans l'URL
if (!isset($_GET['id'])) {
    header("Location: mission.php");
    exit;
}

$missionId = (int)$_GET['id'];
$mission = $mainController->getMissionById($missionId);

// Vérification que la mission existe
if (!$mission) {
    header("Location: mission.php");
    exit;
}

// Traitement du formulaire d'édition
if (isset($_POST['submit'])) {
    $nameBefore = $mission['name'];
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $deadline = trim($_POST['deadline']);
    $difficulty = $_POST['difficulty'];
    $fileType = trim($_POST['fileType']);

    if (empty($name) || empty($description) || empty($deadline) || empty($fileType)) {
        $_SESSION['error'] = "Tous les champs sont obligatoires.";
    } else {
        $result = $mainController->updateMission($nameBefore, $name, $description, $deadline, $difficulty, $fileType);

        if ($result) {
            $_SESSION['success'] = "La mission a été mise à jour avec succès.";
            header("Location: info_mission.php?id=" . $missionId);
            exit;
        } else {
            $_SESSION['error'] = "Une erreur est survenue lors de la mise à jour de la mission.";
        }
    }
}

require_once __DIR__ . "/INCLUDE/header.php";
?>

<div class="container mx-auto px-4 py-8">
    <a href="info_mission.php?id=<?php echo $missionId; ?>" class="inline-block px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-colors mb-4">
        ← Retour à la mission
    </a>

    <div class="max-w-3xl mx-auto bg-gray-800 p-6 rounded-lg shadow-lg text-white">
        <h1 class="text-3xl font-bold mb-6 text-center">Modifier la mission</h1>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg mb-4">
                <?php
                echo htmlspecialchars($_SESSION['error']);
                unset($_SESSION['error']);
                ?>
            </div>
        <?php endif; ?>

        <form method="post" action="" class="space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-300 mb-1">Nom de la mission</label>
                    <input type="text"
                        id="name"
                        name="name"
                        value="<?php echo htmlspecialchars($mission['name']); ?>"
                        class="w-full bg-gray-700 text-white p-3 rounded-lg"
                        required>
                </div>

                <div>
                    <label for="deadline" class="block text-sm font-medium text-gray-300 mb-1">Date limite</label>
                    <input type="date"
                        id="deadline"
                        name="deadline"
                        value="<?php echo date('Y-m-d', strtotime($mission['deadline'])); ?>"
                        class="w-full bg-gray-700 text-white p-3 rounded-lg"
                        required>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="difficulty" class="block text-sm font-medium text-gray-300 mb-1">Difficulté</label>
                    <select id="difficulty"
                        name="difficulty"
                        class="w-full bg-gray-700 text-white p-3 rounded-lg">
                        <option value="facile" <?php echo $mission['difficulty'] === 'facile' ? 'selected' : ''; ?>>Facile</option>
                        <option value="normal" <?php echo $mission['difficulty'] === 'normal' ? 'selected' : ''; ?>>Normal</option>
                        <option value="difficile" <?php echo $mission['difficulty'] === 'difficile' ? 'selected' : ''; ?>>Difficile</option>
                    </select>
                </div>

                <div>
                    <label for="fileType" class="block text-sm font-medium text-gray-300 mb-1">Type de fichier attendu</label>
                    <input type="text"
                        id="fileType"
                        name="fileType"
                        value="<?php echo htmlspecialchars($mission['typeFichier']); ?>"
                        class="w-full bg-gray-700 text-white p-3 rounded-lg"
                        required>
                    <p class="text-xs text-gray-400 mt-1">Exemple: .png, .psd, etc.</p>
                </div>
            </div>

            <div>
                <label for="description" class="block text-sm font-medium text-gray-300 mb-1">Description (Markdown accepté)</label>
                <textarea id="description"
                    name="description"
                    rows="10"
                    class="w-full bg-gray-700 text-white p-3 rounded-lg resize-y"
                    required><?php echo htmlspecialchars($mission['description']); ?></textarea>
            </div>

            <div class="flex justify-end">
                <button type="submit"
                    name="submit"
                    class="bg-blue-500 text-white px-6 py-3 rounded-lg hover:bg-blue-600 transition-colors">
                    Mettre à jour la mission
                </button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . "/INCLUDE/footer.php"; ?>