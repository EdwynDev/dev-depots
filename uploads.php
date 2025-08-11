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

// Récupérer toutes les missions actives de l'utilisateur
$activeMissions = $mainController->getActiveMissions($userId);
$hasMissions = !empty($activeMissions);

// Vérifier les fichiers existants pour toutes les missions actives
$missionFiles = [];
if ($hasMissions) {
    foreach ($activeMissions as $mission) {
        $hasFile = $mainController->checkFileExists($mission['id']);
        if ($hasFile) {
            $missionFiles[$mission['id']] = $hasFile;
        }
    }
}

// Traitement de l'upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file']) && isset($_POST['mission_id'])) {
    $file = $_FILES['file'];
    $fileSize = $file['size'];

    $missionId = $_POST['mission_id'];
    $selectedMission = null;

    // Trouver la mission sélectionnée
    foreach ($activeMissions as $mission) {
        if ($mission['id'] == $missionId) {
            $selectedMission = $mission;
            break;
        }
    }

    if ($selectedMission && !isset($missionFiles[$missionId])) {
        $file = $_FILES['file'];
        $fileName = basename($file['name']);
        $fileTmpName = $file['tmp_name'];
        $fileSize = $file['size'];
        $fileType = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        // CORRECTION: Vérification du type de fichier
        $validTypes = array_map('trim', explode(',', '.zip, ' . strtolower($selectedMission['typeFichier'])));
        $currentFileType = '.' . $fileType;

        $isValidType = false;
        foreach ($validTypes as $validType) {
            if ($validType === $currentFileType) {
                $isValidType = true;
                break;
            }
        }

        if (!$isValidType) {
            echo "<p class='text-red-500'>Type de fichier invalide. Types attendus: " . htmlspecialchars('.zip, ' . strtolower($selectedMission['typeFichier'])) . "</p>";
            exit;
        }

        $domaineId = $user['domaine_id'];
        $domaine = $mainController->checkDomaine($domaineId);
        $uploadDir = __DIR__ . '/uploads/' . $domaine['name'];

        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $targetPath = $uploadDir . '/' . $userId . '-' . $user['username'] . '-' . $fileName;

        if (move_uploaded_file($fileTmpName, $targetPath)) {
            $inserted = $mainController->insertFile($userId, $targetPath, $fileSize, $fileType, $domaineId, $missionId);
            if ($inserted) {
                $incrementationExists = $mainController->checkIncrementationUploads($missionId);
                
                if ($incrementationExists) {
                    $mainController->incrementUploads($missionId);
                } else {
                    $mainController->createIncrementationUploads($missionId);
                }
                
                header("Location: uploads.php");
                exit;
            }
        }
    }
    header("Location: uploads.php");
    exit;
}
require_once __DIR__ . "/INCLUDE/header.php";
?>

<h2 class="text-2xl font-bold mb-6">🚀 Uploader un Fichier</h2>

<div id="loadingScreen" class="fixed inset-0 bg-gray-900 bg-opacity-75 flex items-center justify-center hidden z-50">
    <div class="text-center">
        <p class="text-white text-lg mb-4" id="uploadProgressText"></p>
        <div class="w-64 bg-gray-700 rounded-full h-4">
            <div id="uploadProgressBar" class="bg-blue-500 h-4 rounded-full" style="width: 0%;"></div>
        </div>
    </div>
</div>

<?php if (!$hasMissions): ?>
    <p class="text-gray-400">Vous n'avez pas de mission en cours !</p>
<?php else: ?>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <?php foreach ($activeMissions as $mission): ?>
            <div class="bg-gray-800 rounded-lg p-6 shadow-lg">
                <div class="border-b border-gray-700 pb-4 mb-4">
                    <h3 class="text-xl font-semibold text-white">
                        <a href="https://depots.neopolyworks.fr/info_mission.php?id=<?php echo $mission['id']; ?>" title="<?php echo htmlspecialchars($mission['name']); ?>">
                            <?php echo substr($mission['name'], 0, 45); ?>[...]
                            <br>
                            <span class="px-6 py-4 text-sm text-blue-400 hover:text-blue-300">Cliquez pour voir les détails de la mission.</span>
                        </a>
                    </h3>
                    <p class="text-gray-400 text-sm mt-2">
                        Date limite : <?php echo date('d/m/Y', strtotime($mission['deadline'])); ?>
                    </p>
                    <div class="mt-2">
                        <?php if (isset($missionFiles[$mission['id']])): ?>
                            <div class="bg-green-500 text-white px-3 py-1 rounded-full text-sm">✅ Fichier déjà uploadé</div>
                        <?php else: ?>
                            <div class="bg-yellow-500 text-white px-3 py-1 rounded-full text-sm">⏳ En attente d'upload</div>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if (!isset($missionFiles[$mission['id']])): ?>
                    <div class="mt-4">
                        <form action="uploads.php" method="POST" enctype="multipart/form-data" class="space-y-4" id="uploadForm_<?php echo $mission['id']; ?>">
                            <input type="hidden" name="mission_id" value="<?php echo $mission['id']; ?>">
                            <input type="hidden" id="missionTypesFichier_<?php echo $mission['id']; ?>" value="<?php echo htmlspecialchars($mission['typeFichier']); ?>">
                            <p class="text-gray-300">
                                <strong>Type de fichier attendu :</strong> <?php echo str_contains($mission['typeFichier'], '.zip') ?
                                                                                htmlspecialchars($mission['typeFichier']) :
                                                                                '.zip, ' . strtolower(htmlspecialchars($mission['typeFichier']));
                                                                            ?>
                            </p>

                            <label for="file_<?php echo $mission['id']; ?>" class="flex flex-col items-center justify-center p-6 bg-gray-700 rounded-lg border-2 border-dashed border-gray-600 cursor-pointer hover:border-blue-500 transition-colors">
                                <svg viewBox="0 0 640 512" class="w-12 h-12 text-white">
                                    <path d="M144 480C64.5 480 0 415.5 0 336c0-62.8 40.2-116.2 96.2-135.9c-.1-2.7-.2-5.4-.2-8.1c0-88.4 71.6-160 160-160c59.3 0 111 32.2 138.7 80.2C409.9 102 428.3 96 448 96c53 0 96 43 96 96c0 12.2-2.3 23.8-6.4 34.6C596 238.4 640 290.1 640 352c0 70.7-57.3 128-128 128H144zm79-217c-9.4 9.4-9.4 24.6 0 33.9s24.6 9.4 33.9 0l39-39V392c0 13.3 10.7 24 24 24s24-10.7 24-24V257.9l39 39c9.4 9.4 24.6 9.4 33.9 0s9.4-24.6 0-33.9l-80-80c-9.4-9.4-24.6-9.4-33.9 0l-80 80z" fill="currentColor" />
                                </svg>
                                <p class="text-gray-300 mt-2">Glissez et déposez</p>
                                <p class="text-gray-300">ou</p>
                                <span class="mt-2 px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition-colors">Parcourir</span>
                                <input id="file_<?php echo $mission['id']; ?>" name="file" type="file" required class="hidden" onchange="handleFileSelect(this, <?php echo $mission['id']; ?>)">
                            </label>
                            <div class="text-center text-gray-300" id="selectedFileName_<?php echo $mission['id']; ?>"></div>
                            <button type="submit" class="w-full bg-green-500 text-white py-2 rounded-lg opacity-0 transform translate-y-4 transition-all duration-300" id="submitButton_<?php echo $mission['id']; ?>">
                                Envoyer le fichier
                            </button>
                        </form>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>

    <script>
        function handleFileSelect(input, missionId) {
            const fileName = input.files[0]?.name;
            const selectedFileDiv = document.getElementById(`selectedFileName_${missionId}`);
            const submitButton = document.getElementById(`submitButton_${missionId}`);
            const missionTypesFichier = document.getElementById(`missionTypesFichier_${missionId}`).value;
            const fileExtension = '.' + fileName.split('.').pop().toLowerCase();

            // CORRECTION: Vérification JavaScript du type de fichier
            const validTypes = ('.zip, ' + missionTypesFichier.toLowerCase()).split(',').map(type => type.trim());
            const isValidType = validTypes.includes(fileExtension);

            if (fileName) {
                if (!isValidType) {
                    selectedFileDiv.textContent = `Type de fichier incorrect. Types attendus: .zip, ${missionTypesFichier.toLowerCase()}`;
                    submitButton.classList.remove('opacity-100', 'translate-y-0');
                } else {
                    selectedFileDiv.textContent = `Fichier sélectionné : ${fileName}`;
                    submitButton.classList.add('opacity-100', 'translate-y-0');
                }
            } else {
                selectedFileDiv.textContent = '';
                submitButton.classList.remove('opacity-100', 'translate-y-0');
            }
        }

        document.querySelectorAll('[for^="file_"]').forEach(dropZone => {
            dropZone.addEventListener('dragover', (e) => {
                e.preventDefault();
                dropZone.classList.add('border-blue-500');
            });

            dropZone.addEventListener('dragleave', (e) => {
                e.preventDefault();
                dropZone.classList.remove('border-blue-500');
            });

            dropZone.addEventListener('drop', (e) => {
                e.preventDefault();
                const missionId = dropZone.querySelector('input[type="file"]').id.split('_')[1];
                const input = document.getElementById(`file_${missionId}`);

                if (e.dataTransfer.files.length) {
                    input.files = e.dataTransfer.files;
                    handleFileSelect(input, missionId);
                }
                dropZone.classList.remove('border-blue-500');
            });
        });

        document.querySelectorAll('form').forEach(form => {
            form.addEventListener('submit', (e) => {
                e.preventDefault();
                const formData = new FormData(form);
                const loadingScreen = document.getElementById('loadingScreen');
                const progressBar = document.getElementById('uploadProgressBar');
                const progressText = document.getElementById('uploadProgressText');

                loadingScreen.classList.remove('hidden');

                const xhr = new XMLHttpRequest();
                xhr.open('POST', form.action, true);

                xhr.upload.onprogress = (event) => {
                    if (event.lengthComputable) {
                        const percentComplete = (event.loaded / event.total) * 100;
                        progressBar.style.width = percentComplete + '%';
                        progressText.textContent = `${Math.round(event.loaded / (1024 * 1024))} Mo / ${Math.round(event.total / (1024 * 1024))} Mo`;
                    }
                };

                xhr.onload = () => {
                    if (xhr.status === 200) {
                        // Redirect to the current page to refresh the content
                        window.location.href = 'uploads.php';
                    } else {
                        alert('Une erreur est survenue lors du téléchargement.');
                        loadingScreen.classList.add('hidden');
                    }
                };

                xhr.onerror = () => {
                    alert('Une erreur réseau est survenue.');
                    loadingScreen.classList.add('hidden');
                };

                xhr.send(formData);
            });
        });
    </script>
<?php endif; ?>

<?php require_once __DIR__ . "/INCLUDE/footer.php"; ?>