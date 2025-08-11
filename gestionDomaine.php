<?php
if (session_status() == PHP_SESSION_NONE) {
    session_set_cookie_params(30 * 24 * 60 * 60);
    session_start();
}
if (!isset($_SESSION['discord_user'])) {
    header("Location: https://depots.neopolyworks.fr");
    exit;
}
if (isset($_SESSION['message'])) {
    echo "<script>alert('" . htmlspecialchars($_SESSION['message'], ENT_QUOTES) . "');</script>";
    unset($_SESSION['message']);
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
if (!$isChef) {
    header("Location: https://depots.neopolyworks.fr");
    exit;
}
$domainId = $user['domaine_id'];
$domainName = $mainController->getDomainName($domainId);

// Ajout de membre
if (isset($_POST['add_member'])) {
    $memberId = $_POST['member_id'];
    $memberName = $_POST['member_name'];
    $secondaryDomaine = $_POST['secondary_pole'];
    $added = $mainController->addMemberToDomain($memberId, $memberName, $domainId, $secondaryDomaine);
}

// Validation de mission
if (isset($_POST['validate_mission']) && isset($_POST['mission_id'])) {
    $missionId = $_POST['mission_id'];
    $validated = $mainController->validateMission($missionId);

    if ($validated) {
        $missionWebhook = $mainController->getMissionById($missionId);
        $assigneeId = $missionWebhook['assignee_id'];
        $assigneeName = $mainController->getUsername($assigneeId);

        try {
            $webhookUrl = 'https://discord.com/api/webhooks/1330894000034680893/1f9DKJQFHleYb9B_3ZTnlxOl33dMqW8hZOKmQPpZR2vDdfsRLpvZzLTz01Z45Rv7xn5R';

            $messageContent = sprintf(
                "> La mission de <@%s> a été validée !\n" .
                    "> %s peut maintenant prendre une nouvelle mission.",
                $assigneeId,
                $assigneeName
            );

            $webhookData = [
                'content' => $messageContent,
                'embeds' => [
                    [
                        'title' => $missionWebhook['name'],
                        'description' => '',
                        'color' => hexdec('00FF00'),
                        'fields' => [
                            [
                                'name' => 'Domaine',
                                'value' => $domainName,
                                'inline' => true
                            ]
                        ]
                    ]
                ]
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

    header("Location: gestionDomaine.php");
    exit;
}

// Rejet de mission
if (isset($_POST['reject_mission']) && isset($_POST['mission_id'])) {
    $missionId = $_POST['mission_id'];
    $rejected = $mainController->rejectMission($missionId);
    header("Location: gestionDomaine.php");
    exit;
}

$members = $mainController->getMembersByDomain($domainId);
$membersSecondary = $mainController->getMembersByDomainSecondary($domainId);

// Gestion du tri et du filtrage
$sortBy = isset($_GET['sort']) ? $_GET['sort'] : 'username';
$filterMission = isset($_GET['mission']) ? $_GET['mission'] : 'all';

// Trier les membres
usort($members, function ($a, $b) use ($sortBy, $mainController) {
    if ($sortBy === 'deadline') {
        $missionsA = $mainController->getActiveMissions($a['userId']);
        $missionsB = $mainController->getActiveMissions($b['userId']);

        $deadlineA = PHP_INT_MAX;
        $deadlineB = PHP_INT_MAX;

        if (!empty($missionsA)) {
            $deadlineA = min(array_map(function ($mission) {
                return strtotime($mission['deadline']);
            }, $missionsA));
        }

        if (!empty($missionsB)) {
            $deadlineB = min(array_map(function ($mission) {
                return strtotime($mission['deadline']);
            }, $missionsB));
        }

        return $deadlineA - $deadlineB;
    } else {
        return strcasecmp($a[$sortBy], $b[$sortBy]);
    }
});

// Filtrer les membres
if ($filterMission !== 'all') {
    $members = array_filter($members, function ($member) use ($mainController, $filterMission) {
        $activeMissionsCount = $mainController->hasActiveMission($member['userId']);
        return ($filterMission === 'with_mission' && $activeMissionsCount > 0) ||
            ($filterMission === 'without_mission' && $activeMissionsCount === 0);
    });
}
require_once __DIR__ . "/INCLUDE/header.php";
?>
<style>
    /* Style global pour le wrapper DataTables */
    .dataTables_wrapper {
        color: #d1d5db !important;
    }

    table .dataTables_length {
        color: #ffffff !important;
    }

    /* Style pour les en-têtes de colonnes */
    table.dataTable thead th {
        background-color: #1f2937 !important;
        color: #ffffff !important;
        border-bottom: 2px solid #374151 !important;
    }

    /* Style pour les cellules du corps */
    table.dataTable tbody td {
        background-color: #1f2937 !important;
        color: #d1d5db !important;
        padding: 12px !important;
        border-bottom: 1px solid #374151 !important;
    }

    /* Style pour les lignes au survol */
    table.dataTable tbody tr:hover td {
        background-color: #374151 !important;
    }

    /* Style pour la barre de recherche */
    .dataTables_filter input {
        background-color: #374151 !important;
        border: 1px solid #4b5563 !important;
        color: #d1d5db !important;
        border-radius: 0.375rem !important;
        padding: 0.5rem !important;
        margin-left: 0.5rem !important;
    }

    /* Style pour le sélecteur d'entrées */
    .dataTables_length select {
        background-color: #374151 !important;
        border: 1px solid #4b5563 !important;
        color: #d1d5db !important;
        border-radius: 0.375rem !important;
        padding: 0.5rem !important;
        margin: 0 0.5rem !important;
    }

    /* Style pour la pagination */
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

    /* Style pour l'info de pagination */
    .dataTables_info {
        color: #d1d5db !important;
        padding: 0.5rem !important;
    }

    /* Style pour les lignes impaires */
    table.dataTable tbody tr:nth-child(odd) td {
        background-color: #1f2937 !important;
    }

    /* Style pour les lignes paires */
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

    /* Ajout des styles pour les cartes de membres */
    .member-card {
        transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
    }

    .member-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
    }

    .avatar-wrapper {
        width: 48px;
        height: 48px;
        border-radius: 50%;
        overflow: hidden;
        border: 2px solid #4B5563;
    }

    .avatar-wrapper img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .mission-badge {
        position: absolute;
        top: -8px;
        right: -8px;
        width: 24px;
        height: 24px;
        border-radius: 50%;
        background-color: #3B82F6;
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.75rem;
        border: 2px solid #1F2937;
    }
</style>
<h2 class="text-2xl font-bold mb-4">⚙️ Gestion du domaine <?php echo htmlspecialchars($domainName); ?></h2>

<!-- Formulaire d'ajout de membre -->
<button onclick="toggleFormMission()" class="toggle-form bg-gray-700 text-white p-2 rounded-lg hover:bg-gray-600 transition-colors">
    <svg width="24px" height="24px" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg">
        <g id="SVGRepo_bgCarrier" stroke-width="0"></g>
        <g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g>
        <g id="SVGRepo_iconCarrier">
            <path d="M4.001 21C5.001 24 8.001 29 16.001 29C24.001 29 27.001 24 28.001 21M31 19C31 17 27.001 7 16 7M16 7C5.001 7 1 17 1 19M16 7V3M21.1758 3.6816L20.1758 7.4106M26 5.6797L23.999 9.1427M30.1416 8.8574L27.3136 11.6844M10.8223 3.6816L11.8213 7.4106M5.999 5.6797L7.999 9.1437M1.8574 8.8574L4.6854 11.6854M16.001 12C12.688 12 10.001 14.687 10.001 18C10.001 21.313 12.688 24 16.001 24C19.314 24 22.001 21.313 22.001 18M21.2441 15.0869C20.7001 14.1089 19.8911 13.3009 18.9141 12.7569M18.001 18C18.001 16.896 17.105 16 16.001 16C14.897 16 14.001 16.896 14.001 18C14.001 19.104 14.897 20 16.001 20C17.105 20 18.001 19.104 18.001 18Z" stroke="#ffffff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path>
        </g>
    </svg>
</button>

<div class="create-mission-form flex-col items-center justify-center mt-4" style="display: none;">
    <h3 class="text-xl font-bold mb-4">Ajouter un membre</h3>
    <form method="post" action="" class="w-full max-w-lg">
        <div class="flex justify-between gap-2">
            <div class="mb-4">
                <label for="member_id" class="block text-gray-300">ID Discord du membre :</label>
                <input type="text" id="member_id" name="member_id" required class="w-full px-3 py-2 bg-gray-700 text-white rounded-lg">
            </div>
            <div class="mb-4">
                <label for="member_name" class="block text-gray-300">Nom du membre :</label>
                <input type="text" id="member_name" name="member_name" required class="w-full px-3 py-2 bg-gray-700 text-white rounded-lg">
            </div>
        </div>
        <div class="mb-4">
            <label for="secondary_pole" class="block text-gray-300">Pôle secondaire :</label>
            <select id="secondary_pole" name="secondary_pole" class="w-full px-3 py-2 bg-gray-700 text-white rounded-lg">
                <option value="">Aucun</option>
                <?php foreach ($mainController->giveAllDomaine() as $domain): ?>
                    <option value="<?php echo $domain['id']; ?>"><?php echo htmlspecialchars($domain['name']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <button type="submit" name="add_member" class="w-full bg-green-500 text-white py-2 rounded-lg hover:bg-green-600 transition-colors">Ajouter</button>
    </form>
</div>

<br>
<div class="flex justify-center gap-4 mb-4">
    <a href="analytics.php"><button class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition-colors">Afficher Analytics</button></a>
    <a href="domain_missions.php?id=<?php echo $domainId; ?>"><button class="px-4 py-2 bg-indigo-500 text-white rounded-lg hover:bg-indigo-600 transition-colors">Vue d'ensemble des Missions</button></a>
    <a href="info_commentaire.php"><button class="px-4 py-2 bg-indigo-500 text-white rounded-lg hover:bg-indigo-600 transition-colors">Vue d'ensemble des Commentaires</button></a>
</div>
<hr class="my-4">

<!-- Contrôles de navigation -->
<div class="flex justify-center gap-4 mb-4">
    <button onclick="showSection('primary')" class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition-colors" id="btn-primary">Membres principaux</button>
    <button onclick="showSection('secondary')" class="px-4 py-2 bg-gray-700 text-white rounded-lg hover:bg-gray-600 transition-colors" id="btn-secondary">Membres secondaires</button>
</div>

<!-- Contrôles de filtrage -->
<div class="mb-4 flex flex-wrap gap-4">
    <form method="GET" action="" id="filterForm" class="w-full flex flex-col sm:flex-row gap-4">
        <div class="w-full sm:w-auto">
            <label for="search" class="block text-gray-300 mb-2">Rechercher par nom :</label>
            <input type="text" id="search" name="search" class="w-full px-4 py-2 bg-gray-700 text-white rounded-lg"
                placeholder="Rechercher un membre...">
        </div>

        <div class="w-full sm:w-auto">
            <label for="mission_count" class="block text-gray-300 mb-2">Nombre de missions :</label>
            <select name="mission_count" id="mission_count" class="w-full px-4 py-2 bg-gray-700 text-white rounded-lg">
                <option value="all">Tous</option>
                <option value="2">Aucune mission</option>
                <option value="3">1 mission</option>
                <option value="4">2 missions</option>
                <option value="5">3 missions</option>
            </select>
        </div>
    </form>
</div>

<button id="removeMembersBtn" disabled
    class="mb-6 bg-red-500 text-white px-4 py-2 rounded-lg opacity-50 cursor-not-allowed transition-all duration-200">
    🚫 Retirer les membres sélectionnés (0)
</button>

<!-- Section membres principaux -->
<div id="primary-section">
    <h3 class="text-xl font-bold mb-4">Membres principaux</h3>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        <?php foreach ($members as $member):
            $activeMissions = $mainController->getActiveMissions($member['userId']);
            $missionCount = count($activeMissions);

            // Récupération de l'avatar Discord
            $discordApiUrl = "https://discord.com/api/v10/users/" . $member['userId'];
            $discordHeaders = ["Authorization: Bot " . DISCORD_BOT_TOKEN, "Content-Type: application/json"];

            $ch = curl_init($discordApiUrl);
            curl_setopt_array($ch, [
                CURLOPT_HTTPHEADER => $discordHeaders,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_SSL_VERIFYPEER => true
            ]);

            $response = curl_exec($ch);
            $discordUser = json_decode($response, true);

            if ($discordUser && isset($discordUser['avatar'])) {
                $avatarHash = $discordUser['avatar'];
                $avatarExtension = strpos($avatarHash, 'a_') === 0 ? '.gif' : '.png';
                $avatarUrl = "https://cdn.discordapp.com/avatars/{$member['userId']}/{$avatarHash}{$avatarExtension}?size=256";
            } else {
                $defaultAvatarNumber = rand(0, 4);
                $avatarUrl = "https://cdn.discordapp.com/embed/avatars/{$defaultAvatarNumber}.png";
            }
            curl_close($ch);
        ?>
            <div class="member-card bg-gray-800 rounded-lg shadow-lg p-4">
                <div class="flex items-start gap-4">
                    <div class="relative">
                        <div class="avatar-wrapper">
                            <img src="<?php echo $avatarUrl; ?>" alt="Avatar de <?php echo htmlspecialchars($member['username']); ?>">
                        </div>
                        <?php if ($missionCount > 0): ?>
                            <div class="mission-badge"><?php echo $missionCount; ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="flex-grow">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-lg font-semibold text-white">
                                    <a href="info_user.php?id=<?php echo $member['userId']; ?>" class="hover:text-blue-400 transition-colors">
                                        <?php echo htmlspecialchars($member['username']); ?>
                                    </a>
                                </h3>
                                <p class="text-sm text-gray-400"><?php echo htmlspecialchars($member['userId']); ?></p>
                            </div>
                            <div class="flex items-center">
                                <input type="checkbox" class="member-checkbox w-5 h-5 rounded border-gray-600 bg-gray-700 text-blue-500 focus:ring-blue-500"
                                    data-member-id="<?php echo $member['userId']; ?>"
                                    data-member-name="<?php echo htmlspecialchars($member['username']); ?>">
                            </div>
                        </div>

                        <!-- Missions actives -->
                        <?php if (!empty($activeMissions)): ?>
                            <div class="mt-4 space-y-2">
                                <?php foreach ($activeMissions as $mission): ?>
                                    <div class="flex items-center justify-between gap-2">
                                        <div class="flex-grow bg-gray-700 p-2 rounded text-sm">
                                            <div class="flex justify-between items-center">
                                                <span class="text-gray-300"><?php echo htmlspecialchars($mission['name']); ?></span>
                                                <span class="text-xs text-gray-400">
                                                    <?php echo date('d/m/Y', strtotime($mission['deadline'])); ?>
                                                </span>
                                            </div>
                                        </div>
                                        <div class="flex gap-2">
                                            <form method="post" action="" class="m-0">
                                                <input type="hidden" name="mission_id" value="<?php echo $mission['id']; ?>">
                                                <input type="hidden" name="validate_mission">
                                                <button type="submit" class="flex items-center justify-center p-2 bg-green-500 text-white rounded-lg hover:bg-green-600 transition-colors">
                                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                                    </svg>
                                                </button>
                                            </form>
                                            <form method="post" action="" class="m-0">
                                                <input type="hidden" name="mission_id" value="<?php echo $mission['id']; ?>">
                                                <input type="hidden" name="reject_mission">
                                                <button type="submit" class="flex items-center justify-center p-2 bg-red-500 text-white rounded-lg hover:bg-red-600 transition-colors">
                                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                                                    </svg>
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <p class="mt-4 text-sm text-gray-500">Aucune mission en cours</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Section membres secondaires -->
<div id="secondary-section" class="hidden">
    <h3 class="text-xl font-bold mb-4">Membres secondaires</h3>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        <?php foreach ($membersSecondary as $member):
            $activeMissions = $mainController->getActiveMissions($member['userId']);
            $missionCount = count($activeMissions);

            // Récupération de l'avatar Discord
            $discordApiUrl = "https://discord.com/api/v10/users/" . $member['userId'];
            $discordHeaders = ["Authorization: Bot " . DISCORD_BOT_TOKEN, "Content-Type: application/json"];

            $ch = curl_init($discordApiUrl);
            curl_setopt_array($ch, [
                CURLOPT_HTTPHEADER => $discordHeaders,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_SSL_VERIFYPEER => true
            ]);

            $response = curl_exec($ch);
            $discordUser = json_decode($response, true);

            if ($discordUser && isset($discordUser['avatar'])) {
                $avatarHash = $discordUser['avatar'];
                $avatarExtension = strpos($avatarHash, 'a_') === 0 ? '.gif' : '.png';
                $avatarUrl = "https://cdn.discordapp.com/avatars/{$member['userId']}/{$avatarHash}{$avatarExtension}?size=256";
            } else {
                $defaultAvatarNumber = rand(0, 4);
                $avatarUrl = "https://cdn.discordapp.com/embed/avatars/{$defaultAvatarNumber}.png";
            }
            curl_close($ch);
        ?>
            <div class="member-card bg-gray-800 rounded-lg shadow-lg p-4">
                <div class="flex items-start gap-4">
                    <div class="relative">
                        <div class="avatar-wrapper">
                            <img src="<?php echo $avatarUrl; ?>" alt="Avatar de <?php echo htmlspecialchars($member['username']); ?>">
                        </div>
                        <?php if ($missionCount > 0): ?>
                            <div class="mission-badge"><?php echo $missionCount; ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="flex-grow">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-lg font-semibold text-white">
                                    <a href="info_user.php?id=<?php echo $member['userId']; ?>" class="hover:text-blue-400 transition-colors">
                                        <?php echo htmlspecialchars($member['username']); ?>
                                    </a>
                                </h3>
                                <p class="text-sm text-gray-400"><?php echo htmlspecialchars($member['userId']); ?></p>
                            </div>
                            <div class="flex items-center">
                                <input type="checkbox" class="member-checkbox w-5 h-5 rounded border-gray-600 bg-gray-700 text-blue-500 focus:ring-blue-500"
                                    data-member-id="<?php echo $member['userId']; ?>"
                                    data-member-name="<?php echo htmlspecialchars($member['username']); ?>">
                            </div>
                        </div>

                        <!-- Missions actives -->
                        <?php if (!empty($activeMissions)): ?>
                            <div class="mt-4 space-y-2">
                                <?php foreach ($activeMissions as $mission): ?>
                                    <div class="flex items-center justify-between gap-2">
                                        <div class="flex-grow bg-gray-700 p-2 rounded text-sm">
                                            <div class="flex justify-between items-center">
                                                <span class="text-gray-300"><?php echo htmlspecialchars($mission['name']); ?></span>
                                                <span class="text-xs text-gray-400">
                                                    <?php echo date('d/m/Y', strtotime($mission['deadline'])); ?>
                                                </span>
                                            </div>
                                        </div>
                                        <div class="flex gap-2">
                                            <form method="post" action="" class="m-0">
                                                <input type="hidden" name="mission_id" value="<?php echo $mission['id']; ?>">
                                                <input type="hidden" name="validate_mission">
                                                <button type="submit" class="flex items-center justify-center p-2 bg-green-500 text-white rounded-lg hover:bg-green-600 transition-colors">
                                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                                    </svg>
                                                </button>
                                            </form>
                                            <form method="post" action="" class="m-0">
                                                <input type="hidden" name="mission_id" value="<?php echo $mission['id']; ?>">
                                                <input type="hidden" name="reject_mission">
                                                <button type="submit" class="flex items-center justify-center p-2 bg-red-500 text-white rounded-lg hover:bg-red-600 transition-colors">
                                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                                                    </svg>
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <p class="mt-4 text-sm text-gray-500">Aucune mission en cours</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<script>
    function toggleFormMission() {
        const form = document.querySelector('.create-mission-form');
        const button = document.querySelector('.toggle-form');
        const currentDisplay = form.style.display;

        if (currentDisplay === 'none') {
            form.style.display = 'flex';
            button.innerHTML = '<svg width="24px" height="24px" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"> <path d="M2.68936 6.70456C2.52619 6.32384 2.08528 6.14747 1.70456 6.31064C1.32384 6.47381 1.14747 6.91472 1.31064 7.29544L2.68936 6.70456ZM15.5872 13.3287L15.3125 12.6308L15.5872 13.3287ZM9.04145 13.7377C9.26736 13.3906 9.16904 12.926 8.82185 12.7001C8.47466 12.4742 8.01008 12.5725 7.78417 12.9197L9.04145 13.7377ZM6.37136 15.091C6.14545 15.4381 6.24377 15.9027 6.59096 16.1286C6.93815 16.3545 7.40273 16.2562 7.62864 15.909L6.37136 15.091ZM22.6894 7.29544C22.8525 6.91472 22.6762 6.47381 22.2954 6.31064C21.9147 6.14747 21.4738 6.32384 21.3106 6.70456L22.6894 7.29544ZM19 11.1288L18.4867 10.582V10.582L19 11.1288ZM19.9697 13.1592C20.2626 13.4521 20.7374 13.4521 21.0303 13.1592C21.3232 12.8663 21.3232 12.3914 21.0303 12.0985L19.9697 13.1592ZM11.25 16.5C11.25 16.9142 11.5858 17.25 12 17.25C12.4142 17.25 12.75 16.9142 12.75 16.5H11.25ZM16.3714 15.909C16.5973 16.2562 17.0619 16.3545 17.409 16.1286C17.7562 15.9027 17.8545 15.4381 17.6286 15.091L16.3714 15.909ZM5.53033 11.6592C5.82322 11.3663 5.82322 10.8914 5.53033 10.5985C5.23744 10.3056 4.76256 10.3056 4.46967 10.5985L5.53033 11.6592ZM2.96967 12.0985C2.67678 12.3914 2.67678 12.8663 2.96967 13.1592C3.26256 13.4521 3.73744 13.4521 4.03033 13.1592L2.96967 12.0985ZM12 13.25C8.77611 13.25 6.46133 11.6446 4.9246 9.98966C4.15645 9.16243 3.59325 8.33284 3.22259 7.71014C3.03769 7.3995 2.90187 7.14232 2.8134 6.96537C2.76919 6.87696 2.73689 6.80875 2.71627 6.76411C2.70597 6.7418 2.69859 6.7254 2.69411 6.71533C2.69187 6.7103 2.69036 6.70684 2.68957 6.70503C2.68917 6.70413 2.68896 6.70363 2.68892 6.70355C2.68891 6.70351 2.68893 6.70357 2.68901 6.70374C2.68904 6.70382 2.68913 6.70403 2.68915 6.70407C2.68925 6.7043 2.68936 6.70456 2 7C1.31064 7.29544 1.31077 7.29575 1.31092 7.29609C1.31098 7.29624 1.31114 7.2966 1.31127 7.2969C1.31152 7.29749 1.31183 7.2982 1.31218 7.299C1.31287 7.30062 1.31376 7.30266 1.31483 7.30512C1.31698 7.31003 1.31988 7.31662 1.32353 7.32483C1.33083 7.34125 1.34115 7.36415 1.35453 7.39311C1.38127 7.45102 1.42026 7.5332 1.47176 7.63619C1.57469 7.84206 1.72794 8.13175 1.93366 8.47736C2.34425 9.16716 2.96855 10.0876 3.8254 11.0103C5.53867 12.8554 8.22389 14.75 12 14.75V13.25ZM15.3125 12.6308C14.3421 13.0128 13.2417 13.25 12 13.25V14.75C13.4382 14.75 14.7246 14.4742 15.8619 14.0266L15.3125 12.6308ZM7.78417 12.9197L6.37136 15.091L7.62864 15.909L9.04145 13.7377L7.78417 12.9197ZM22 7C21.3106 6.70456 21.3107 6.70441 21.3108 6.70427C21.3108 6.70423 21.3108 6.7041 21.3109 6.70402C21.3109 6.70388 21.311 6.70376 21.311 6.70368C21.3111 6.70352 21.3111 6.70349 21.3111 6.7036C21.311 6.7038 21.3107 6.70452 21.3101 6.70576C21.309 6.70823 21.307 6.71275 21.3041 6.71924C21.2983 6.73223 21.2889 6.75309 21.2758 6.78125C21.2495 6.83757 21.2086 6.92295 21.1526 7.03267C21.0406 7.25227 20.869 7.56831 20.6354 7.9432C20.1669 8.69516 19.4563 9.67197 18.4867 10.582L19.5133 11.6757C20.6023 10.6535 21.3917 9.56587 21.9085 8.73646C22.1676 8.32068 22.36 7.9668 22.4889 7.71415C22.5533 7.58775 22.602 7.48643 22.6353 7.41507C22.6519 7.37939 22.6647 7.35118 22.6737 7.33104C22.6782 7.32097 22.6818 7.31292 22.6844 7.30696C22.6857 7.30398 22.6867 7.30153 22.6876 7.2996C22.688 7.29864 22.6883 7.29781 22.6886 7.29712C22.6888 7.29677 22.6889 7.29646 22.689 7.29618C22.6891 7.29604 22.6892 7.29585 22.6892 7.29578C22.6893 7.29561 22.6894 7.29544 22 7ZM18.4867 10.582C17.6277 11.3882 16.5739 12.1343 15.3125 12.6308L15.8619 14.0266C17.3355 13.4466 18.5466 12.583 19.5133 11.6757L18.4867 10.582ZM18.4697 11.6592L19.9697 13.1592L21.0303 12.0985L19.5303 10.5985L18.4697 11.6592ZM11.25 14V16.5H12.75V14H11.25ZM14.9586 13.7377L16.3714 15.909L17.6286 15.091L16.2158 12.9197L14.9586 13.7377ZM4.46967 10.5985L2.96967 12.0985L4.03033 13.1592L5.53033 11.6592L4.46967 10.5985Z" fill="#ffffff"></path> </g></svg>';
        } else {
            form.style.display = 'none';
            button.innerHTML = '<svg width="24px" height="24px" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"> <path d="M4.001 21C5.001 24 8.001 29 16.001 29C24.001 29 27.001 24 28.001 21M31 19C31 17 27.001 7 16 7M16 7C5.001 7 1 17 1 19M16 7V3M21.1758 3.6816L20.1758 7.4106M26 5.6797L23.999 9.1427M30.1416 8.8574L27.3136 11.6844M10.8223 3.6816L11.8213 7.4106M5.999 5.6797L7.999 9.1437M1.8574 8.8574L4.6854 11.6854M16.001 12C12.688 12 10.001 14.687 10.001 18C10.001 21.313 12.688 24 16.001 24C19.314 24 22.001 21.313 22.001 18M21.2441 15.0869C20.7001 14.1089 19.8911 13.3009 18.9141 12.7569M18.001 18C18.001 16.896 17.105 16 16.001 16C14.897 16 14.001 16.896 14.001 18C14.001 19.104 14.897 20 16.001 20C17.105 20 18.001 19.104 18.001 18Z" stroke="#ffffff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path> </g></svg>';
        }
    }
    document.addEventListener('DOMContentLoaded', function() {
        const selectedMembers = new Set();
        const checkboxes = document.querySelectorAll('.member-checkbox');
        const removeBtn = document.getElementById('removeMembersBtn');

        function updateRemoveButton() {
            const count = selectedMembers.size;
            removeBtn.textContent = `🚫 Retirer les membres sélectionnés (${count})`;
            if (count > 0) {
                removeBtn.disabled = false;
                removeBtn.classList.remove('opacity-50', 'cursor-not-allowed');
            } else {
                removeBtn.disabled = true;
                removeBtn.classList.add('opacity-50', 'cursor-not-allowed');
            }
        }

        checkboxes.forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                const memberData = {
                    id: this.dataset.memberId,
                    name: this.dataset.memberName
                };

                if (this.checked) {
                    selectedMembers.add(JSON.stringify(memberData));
                } else {
                    selectedMembers.delete(JSON.stringify(memberData));
                }

                updateRemoveButton();
            });
        });

        removeBtn.addEventListener('click', async function() {
            if (selectedMembers.size === 0) return;

            if (!confirm('Êtes-vous sûr de vouloir retirer ces membres ?')) return;

            try {
                const membersToRemove = Array.from(selectedMembers).map(m => JSON.parse(m));
                const userIds = membersToRemove.map(member => member.id);
                
                const response = await fetch('remove_member.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        userIds: userIds
                    })
                });
                
                const result = await response.json();
                if (result.success) {
                    window.location.reload();
                } else {
                    alert('Erreur: ' + (result.message || 'Erreur inconnue lors de la suppression'));
                    console.error('Erreur détaillée:', result);
                }
            } catch (error) {
                console.error('Erreur technique:', error);
                alert('Une erreur est survenue lors de la suppression des membres');
            }
        });

        // Gestion des filtres
        const searchInput = document.getElementById('search');
        const missionCount = document.getElementById('mission_count');

        function filterMembers() {
            const searchValue = searchInput.value.toLowerCase();
            const missionCountValue = missionCount.value;
            const currentSection = document.getElementById('primary-section').classList.contains('hidden') ?
                document.getElementById('secondary-section') : document.getElementById('primary-section');
            const memberCards = currentSection.querySelectorAll('.member-card');

            memberCards.forEach(card => {
                const username = card.querySelector('h3').textContent.toLowerCase();
                const missionsCount = card.querySelectorAll('.bg-gray-700').length;

                let showCard = username.includes(searchValue);

                if (missionCountValue !== 'all') {
                    showCard = showCard && (missionsCount == missionCountValue - 1);
                }

                card.style.display = showCard ? '' : 'none';
            });
        }

        searchInput.addEventListener('input', filterMembers);
        missionCount.addEventListener('change', filterMembers);
    });

    function showSection(type) {
        const primarySection = document.getElementById('primary-section');
        const secondarySection = document.getElementById('secondary-section');
        const primaryBtn = document.getElementById('btn-primary');
        const secondaryBtn = document.getElementById('btn-secondary');

        if (type === 'primary') {
            primarySection.classList.remove('hidden');
            secondarySection.classList.add('hidden');
            primaryBtn.classList.add('bg-blue-500');
            primaryBtn.classList.remove('bg-gray-700');
            secondaryBtn.classList.remove('bg-blue-500');
            secondaryBtn.classList.add('bg-gray-700');
        } else {
            primarySection.classList.add('hidden');
            secondarySection.classList.remove('hidden');
            primaryBtn.classList.remove('bg-blue-500');
            primaryBtn.classList.add('bg-gray-700');
            secondaryBtn.classList.add('bg-blue-500');
            secondaryBtn.classList.remove('bg-gray-700');
        }
        
        filterMembers();
    }
</script>

<?php require_once __DIR__ . "/INCLUDE/footer.php"; ?>