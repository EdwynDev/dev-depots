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
$isRef = $mainController->checkIfIsRef($userId);
$userDomaine = $mainController->checkDomaine($user['domaine_id']);
$allDomaines = $mainController->giveAllDomaine();

function getUploadsPath($domainName, $fileName)
{
    return realpath(__DIR__ . "/uploads/" . basename($domainName) . "/" . basename($fileName));
}

$uploadsDir = __DIR__ . '/uploads';
$files = [];

foreach ($allDomaines as $domain) {
    $domainName = $domain['name'];
    $domainDir = realpath("$uploadsDir/" . basename($domainName));
    if ($domainDir && is_dir($domainDir)) {
        $domainFiles = scandir($domainDir);
        foreach ($domainFiles as $file) {
            if ($file !== '.' && $file !== '..') {
                $filePath = getUploadsPath($domainName, $file);
                if ($filePath && strpos($filePath, realpath($uploadsDir)) === 0) {
                    $uploadInfo = $mainController->getUploadInfo($filePath);
                    $files[] = [
                        'name' => htmlspecialchars($file),
                        'domain' => htmlspecialchars($domainName),
                        'path' => $filePath,
                        'size' => round(filesize($filePath) / (1024 * 1024), 2),
                        'user' => $uploadInfo['user'],
                        'created_at' => $uploadInfo['created_at'],
                        'mission' => $uploadInfo['mission'],
                        'userId' => $uploadInfo['user']['userId']
                    ];
                }
            }
        }
    }
}

// Récupérer les paramètres de filtrage
$searchQuery = isset($_GET['search']) ? trim($_GET['search']) : '';
$sortBy = isset($_GET['sort']) ? $_GET['sort'] : 'date';
$order = isset($_GET['order']) ? $_GET['order'] : 'desc';
$filteredDomain = isset($_GET['domain']) ? $_GET['domain'] : '';

// Filtrer par domaine si nécessaire
if ($filteredDomain && in_array($filteredDomain, array_column($allDomaines, 'name'))) {
    $files = array_filter($files, function ($file) use ($filteredDomain) {
        return $file['domain'] === $filteredDomain;
    });
}

// Appliquer le filtrage et le tri
$files = $mainController->filterAndSortFiles($files, $searchQuery, $sortBy, $order);

require_once __DIR__ . "/INCLUDE/header.php";
?>

<style>
    .dataTables_wrapper {
        color: #d1d5db !important;
    }

    table .dataTables_length {
        color: #ffffff !important;
    }

    table.dataTable thead th {
        background-color: #1f2937 !important;
        color: #ffffff !important;
        border-bottom: 2px solid #374151 !important;
    }

    table.dataTable tbody td {
        background-color: #1f2937 !important;
        color: #d1d5db !important;
        padding: 12px !important;
        border-bottom: 1px solid #374151 !important;
    }

    table.dataTable tbody tr:hover td {
        background-color: #374151 !important;
    }

    .dataTables_filter input {
        background-color: #374151 !important;
        border: 1px solid #4b5563 !important;
        color: #d1d5db !important;
        border-radius: 0.375rem !important;
        padding: 0.5rem !important;
        margin-left: 0.5rem !important;
    }

    .dataTables_length select {
        background-color: #374151 !important;
        border: 1px solid #4b5563 !important;
        color: #d1d5db !important;
        border-radius: 0.375rem !important;
        padding: 0.5rem !important;
        margin: 0 0.5rem !important;
    }

    .dataTables_paginate .paginate_button {
        background-color: #374151 !important;
        color: #d1d5db !important;
        border: 1px solid #4b5563 !important;
        border-radius: 0.375rem !important;
        padding: 0.5rem 1rem !important;
        margin: 0 0.25rem !important;
    }

    .dataTables_paginate .paginate_button:hover {
        background-color: #4b5563 !important;
        color: #ffffff !important;
    }

    .dataTables_paginate .paginate_button.current {
        background-color: #3b82f6 !important;
        color: #ffffff !important;
        border-color: #3b82f6 !important;
    }

    .dataTables_paginate .paginate_button.disabled {
        background-color: #1f2937 !important;
        color: #6b7280 !important;
        cursor: not-allowed !important;
    }

    .dataTables_info {
        color: #d1d5db !important;
        padding: 0.5rem !important;
    }

    table.dataTable tbody tr:nth-child(odd) td {
        background-color: #1f2937 !important;
    }

    table.dataTable tbody tr:nth-child(even) td {
        background-color: #111827 !important;
    }

    .dataTables_wrapper .dataTables_paginate .paginate_button {
        background-color: #374151 !important;
        color: #d1d5db !important;
        border: 1px solid #4b5563 !important;
        border-radius: 0.375rem !important;
        padding: 0.5rem 1rem !important;
        margin: 0 0.25rem !important;
    }
</style>
<h2 class="text-2xl font-bold mb-4">🗃️ Liste des Fichiers Uploadés</h2>

<div id="downloadModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-gray-800 p-6 rounded-lg shadow-xl max-w-lg w-full">
        <h3 class="text-xl font-bold mb-4 text-white">Téléchargement en cours...</h3>
        <div class="w-full bg-gray-700 rounded-full h-4 mb-4">
            <div id="downloadProgress" class="bg-blue-500 h-4 rounded-full transition-all duration-200" style="width: 0%"></div>
        </div>
        <p id="downloadStatus" class="text-gray-300 text-center"></p>
    </div>
</div>

<div class="mb-4 flex flex-wrap gap-4">
    <form method="GET" action="" id="filterForm" class="w-full flex flex-col sm:flex-row gap-4">
        <div class="w-full sm:w-auto">
            <label for="search" class="block text-gray-300 mb-2">Rechercher :</label>
            <input type="text"
                id="search"
                name="search"
                value="<?php echo htmlspecialchars($searchQuery); ?>"
                class="w-full px-4 py-2 bg-gray-700 text-white rounded-lg"
                placeholder="Rechercher...">
        </div>

        <div class="w-full sm:w-auto">
            <label for="domain" class="block text-gray-300 mb-2">Domaine :</label>
            <select name="domain" id="domain" class="w-full px-4 py-2 bg-gray-700 text-white rounded-lg">
                <option value="">Tous</option>
                <?php foreach ($allDomaines as $domain): ?>
                    <option value="<?php echo htmlspecialchars($domain['name']); ?>"
                        <?php echo ($filteredDomain === $domain['name']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($domain['name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="w-full sm:w-auto">
            <label for="sort" class="block text-gray-300 mb-2">Trier par :</label>
            <select name="sort" id="sort" class="w-full px-4 py-2 bg-gray-700 text-white rounded-lg">
                <option value="date" <?php echo ($sortBy === 'date') ? 'selected' : ''; ?>>Date</option>
                <option value="name" <?php echo ($sortBy === 'name') ? 'selected' : ''; ?>>Nom</option>
                <option value="size" <?php echo ($sortBy === 'size') ? 'selected' : ''; ?>>Taille</option>
                <option value="domain" <?php echo ($sortBy === 'domain') ? 'selected' : ''; ?>>Domaine</option>
                <option value="user" <?php echo ($sortBy === 'user') ? 'selected' : ''; ?>>Utilisateur</option>
            </select>
        </div>

        <div class="w-full sm:w-auto">
            <label for="order" class="block text-gray-300 mb-2">Ordre :</label>
            <select name="order" id="order" class="w-full px-4 py-2 bg-gray-700 text-white rounded-lg">
                <option value="desc" <?php echo ($order === 'desc') ? 'selected' : ''; ?>>Décroissant</option>
                <option value="asc" <?php echo ($order === 'asc') ? 'selected' : ''; ?>>Croissant</option>
            </select>
        </div>
    </form>
</div>
<button id="downloadSelected" disabled
    class="mb-6 bg-blue-500 text-white px-4 py-2 rounded-lg opacity-50 cursor-not-allowed transition-all duration-200">
    📥 Télécharger la sélection (0)
</button>


<div class="grid gap-4">
    <?php foreach ($files as $file): ?>
        <div class="bg-gray-800 rounded-lg shadow-lg p-4">
            <div class="flex flex-col sm:flex-row items-start sm:items-center gap-4">
                <?php if (
                    ($isRef || $isChef || $file['userId'] === $userId)
                ): ?>
                    <div class="flex-shrink-0">
                        <input type="checkbox"
                            class="file-checkbox w-5 h-5 rounded border-gray-600 bg-gray-700 text-blue-500 focus:ring-blue-500"
                            data-file-path="<?php echo htmlspecialchars($file['path']); ?>"
                            data-file-name="<?php echo htmlspecialchars($file['name']); ?>">
                    </div>
                <?php endif; ?>

                <div class="flex-grow w-full">
                    <div class="grid grid-cols-1 sm:grid-cols-6 gap-4">
                        <div class="col-span-1 sm:col-span-2">
                            <h3 class="text-lg font-semibold text-white"><?php echo $file['name']; ?></h3>
                            <p class="text-sm text-gray-400">Mission:
                                <a href="info_mission.php?id=<?php echo $file['mission']['id']; ?>"
                                    class="text-blue-400 hover:text-blue-300">
                                    <?php echo htmlspecialchars($file['mission']['name']); ?>
                                </a>
                            </p>
                        </div>

                        <div class="text-sm text-gray-300">
                            <p><span class="text-gray-500">Par:</span> <?php echo $file['user']['username']; ?></p>
                            <p><span class="text-gray-500">Le:</span> <?php echo date('d/m/Y', strtotime($file['created_at'])); ?></p>
                        </div>

                        <div class="text-sm text-gray-300">
                            <p><span class="text-gray-500">Domaine:</span> <?php echo $file['domain']; ?></p>
                            <p><span class="text-gray-500">Taille:</span> <?php echo $file['size']; ?> MB</p>
                        </div>

                        <div class="col-span-1 sm:col-span-2 flex justify-start sm:justify-end gap-2 mt-4 sm:mt-0">
                            <?php if (
                                ($isRef || $isChef || $file['userId'] === $userId) &&
                                (($isRef || $isChef) && $file['domain'] == $userDomaine['name'] ||
                                    ($userFileInfo = $mainController->checkIfInDatabase($file['userId'])) &&
                                    ($secondaryDomainName = $mainController->checkDomaine($userFileInfo['domaine_id_secondary']))['name'] == $userDomaine['name'] ||
                                    $file['userId'] == $userId)
                            ): ?>
                                <a href="delete_file.php?path=<?php echo urlencode($file['path']); ?>"
                                    onclick="return confirm('Êtes-vous sûr de vouloir supprimer ce fichier ?');"
                                    class="w-10 h-10 p-2 bg-red-500 text-white p-2 rounded-lg hover:bg-red-600 transition-colors">
                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                                    </svg>
                                </a>
                            <?php endif; ?>
                            <?php if (
                                ($isRef || $isChef || $file['userId'] === $userId)
                            ): ?>
                                <a href="download_file.php?path=<?php echo urlencode($file['path']); ?>"
                                    class="w-10 h-10 p-2 bg-green-500 text-white p-2 rounded-lg hover:bg-green-600 transition-colors">
                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm3.293-7.707a1 1 0 011.414 0L9 10.586V3a1 1 0 112 0v7.586l1.293-1.293a1 1 0 111.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                                    </svg>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        document.getElementById('search').addEventListener('input', debounce(function(e) {
            document.getElementById('filterForm').submit();
        }, 500));

        document.getElementById('domain').addEventListener('change', function() {
            document.getElementById('filterForm').submit();
        });

        document.getElementById('sort').addEventListener('change', function() {
            document.getElementById('filterForm').submit();
        });

        document.getElementById('order').addEventListener('change', function() {
            document.getElementById('filterForm').submit();
        });

        function debounce(func, wait) {
            let timeout;
            return function executedFunction(...args) {
                const later = () => {
                    clearTimeout(timeout);
                    func(...args);
                };
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        }
        const selectedFiles = new Set();
        const checkboxes = document.querySelectorAll('.file-checkbox');
        const downloadBtn = document.getElementById('downloadSelected');
        const downloadModal = document.getElementById('downloadModal');
        const downloadProgress = document.getElementById('downloadProgress');
        const downloadStatus = document.getElementById('downloadStatus');

        function updateDownloadButton() {
            const count = selectedFiles.size;
            downloadBtn.textContent = `📥 Télécharger la sélection (${count})`;
            if (count > 0) {
                downloadBtn.disabled = false;
                downloadBtn.classList.remove('opacity-50', 'cursor-not-allowed');
            } else {
                downloadBtn.disabled = true;
                downloadBtn.classList.add('opacity-50', 'cursor-not-allowed');
            }
        }

        checkboxes.forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                const fileData = {
                    path: this.dataset.filePath,
                    name: this.dataset.fileName
                };

                if (this.checked) {
                    selectedFiles.add(JSON.stringify(fileData));
                } else {
                    selectedFiles.delete(JSON.stringify(fileData));
                }

                updateDownloadButton();
            });
        });

        downloadBtn.addEventListener('click', async function() {
            if (selectedFiles.size === 0) return;

            downloadModal.classList.remove('hidden');
            downloadProgress.style.width = '0%';
            downloadStatus.textContent = 'Préparation du téléchargement...';

            try {
                const response = await fetch('batch_download.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(Array.from(selectedFiles).map(f => JSON.parse(f)))
                });

                if (response.ok) {
                    const blob = await response.blob();
                    const url = window.URL.createObjectURL(blob);
                    const a = document.createElement('a');
                    a.href = url;
                    a.download = 'files.zip';
                    document.body.appendChild(a);
                    a.click();
                    window.URL.revokeObjectURL(url);
                    document.body.removeChild(a);
                }
            } catch (error) {
                console.error('Erreur lors du téléchargement:', error);
                downloadStatus.textContent = 'Erreur lors du téléchargement';
            } finally {
                setTimeout(() => {
                    downloadModal.classList.add('hidden');
                }, 1000);
            }
        });
    });
</script>

<?php require_once __DIR__ . "/INCLUDE/footer.php"; ?>