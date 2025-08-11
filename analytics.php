<?php
if (session_status() == PHP_SESSION_NONE) {
    session_set_cookie_params(30 * 24 * 60 * 60);
    session_start();
}

require_once __DIR__ . '/CONTROLLERS/MainController.php';

use Controllers\MainController;

$mainController = new MainController();

$currentDate = date('Y-m-d');

$completedByDomain = [];
$pendingByDomain = [];
$availableByDomain = [];
$peopleByDomain = [];
$domainNames = [];

$domains = $mainController->giveAllDomaine();
foreach ($domains as $domain) {
    $domainId = $domain['id'];
    $domainName = $domain['name'];

    // Filtre pour les missions complétées
    $completedMissions = array_filter($mainController->getMissionsByDomainCompleted($domainId), function ($mission) use ($currentDate) {
        return $mission['deadline'] >= $currentDate;
    });

    // Filtre pour les missions en cours
    $pendingMissions = array_filter($mainController->getMissionsByDomainPending($domainId), function ($mission) use ($currentDate) {
        return $mission['deadline'] >= $currentDate && $mission['start_date'] <= $currentDate;
    });

    // Filtre pour les missions disponibles
    $availableMissions = array_filter($mainController->getMissionsByDomainAvailable($domainId), function ($mission) use ($currentDate) {
        return $mission['deadline'] >= $currentDate && $mission['start_date'] <= $currentDate;
    });

    $completedByDomain[] = count($completedMissions);
    $pendingByDomain[] = count($pendingMissions);
    $availableByDomain[] = count($availableMissions);
    $peopleByDomain[] = count($mainController->getPeopleByDomain($domainId));
    $domainNames[] = $domainName;
}

$missions = $mainController->getAllMissions();
$events = [];
foreach ($missions as $mission) {
    if ($mission['deadline'] >= $currentDate && $mission['start_date'] <= $currentDate) {
        $events[] = [
            'title' => $mission['name'],
            'start' => $mission['start_date'],
            'end' => $mission['deadline'],
            'color' => $mission['status'] == 'completed' ? 'rgba(85, 192, 75, 0.6)' : ($mission['status'] == 'available' ? 'rgba(75, 139, 192, 0.6)' : 'rgba(192, 137, 75, 0.6)')
        ];
    }
}

$members = $mainController->getAllMembers();

require_once __DIR__ . "/INCLUDE/header.php";
?>

<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.11.3/css/jquery.dataTables.min.css">
<script type="text/javascript" charset="utf8" src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script type="text/javascript" charset="utf8" src="https://cdn.datatables.net/1.11.3/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/main.min.css">

<a href="javascript:history.back()" class="inline-block px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition-colors mb-4">Retour</a>

<h2 class="text-2xl font-bold mb-4">Statistiques des domaines</h2>
<canvas id="myChart" class="mb-8"></canvas>

<h2 class="text-2xl font-bold mb-4">Liste des membres</h2>
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
</style>
<div class="overflow-x-auto rounded-lg shadow-lg bg-gray-800">
    <table id="membersTable" class="min-w-full divide-y divide-gray-700">
        <thead class="bg-gray-700">
            <tr>
                <th class="px-6 py-3 text-left text-sm font-semibold text-gray-300 uppercase tracking-wider">ID Discord</th>
                <th class="px-6 py-3 text-left text-sm font-semibold text-gray-300 uppercase tracking-wider">Nom d'utilisateur</th>
                <th class="px-6 py-3 text-left text-sm font-semibold text-gray-300 uppercase tracking-wider">Domaine Principal</th>
                <th class="px-6 py-3 text-left text-sm font-semibold text-gray-300 uppercase tracking-wider">Domaine Secondaire</th>
                <th class="px-6 py-3 text-left text-sm font-semibold text-gray-300 uppercase tracking-wider">Chef</th>
                <th class="px-6 py-3 text-left text-sm font-semibold text-gray-300 uppercase tracking-wider">Date de création</th>
            </tr>
        </thead>
        <tbody class="bg-gray-800 divide-y divide-gray-700">
            <?php foreach ($members as $member) : ?>
                <tr class="hover:bg-gray-750 transition-colors">
                    <td class="px-6 py-4 text-sm text-gray-300"><?php echo htmlspecialchars($member['userId']); ?></td>
                    <td class="px-6 py-4 text-sm text-gray-300"><?php echo htmlspecialchars($member['username']); ?></td>
                    <td class="px-6 py-4 text-sm text-gray-300"><?php echo htmlspecialchars($mainController->getDomainName($member['domaine_id'])); ?></td>
                    <td class="px-6 py-4 text-sm text-gray-300"><?php echo $member['domaine_id_secondary'] ? htmlspecialchars($mainController->getDomainName($member['domaine_id_secondary'])) : 'N/A'; ?></td>
                    <td class="px-6 py-4 text-sm text-gray-300">
                        <?php if ($member['chefs']) : ?>
                            <span class="inline-block px-2 py-1 bg-green-500 text-white text-xs rounded-full">Oui</span>
                        <?php else : ?>
                            <span class="inline-block px-2 py-1 bg-red-500 text-white text-xs rounded-full">Non</span>
                        <?php endif; ?>
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-300"><?php echo htmlspecialchars($member['created_at']); ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<script>
    $(document).ready(function() {
        $('#membersTable').DataTable({
            language: {
                url: 'https://cdn.datatables.net/plug-ins/1.11.3/i18n/fr_fr.json'
            },
            dom: '<"flex justify-between items-center mb-4"<"flex"l><"flex"f>>rt<"flex justify-between items-center mt-4"<"flex"i><"flex"p>>',
            pagingType: 'full_numbers',
            lengthMenu: [10, 25, 50, 100],
            pageLength: 10,
            responsive: true,
            order: [
                [5, 'desc']
            ]
        });
    });

    const data = {
        labels: <?php echo json_encode($domainNames); ?>,
        completedByDomain: <?php echo json_encode($completedByDomain); ?>,
        pendingByDomain: <?php echo json_encode($pendingByDomain); ?>,
        availableByDomain: <?php echo json_encode($availableByDomain); ?>,
        peopleByDomain: <?php echo json_encode($peopleByDomain); ?>
    };

    new Chart(document.getElementById('myChart'), {
        type: 'bar',
        data: {
            labels: data.labels,
            datasets: [{
                label: 'Missions complétées',
                data: data.completedByDomain,
                backgroundColor: 'rgba(85, 192, 75, 0.6)'
            }, {
                label: 'Missions en cours',
                data: data.pendingByDomain,
                backgroundColor: 'rgba(192, 137, 75, 0.6)'
            }, {
                label: 'Missions disponibles',
                data: data.availableByDomain,
                backgroundColor: 'rgba(75, 139, 192, 0.6)'
            }, {
                label: 'Membres',
                data: data.peopleByDomain,
                backgroundColor: 'rgba(192, 75, 75, 0.6)'
            }]
        },
        options: {
            scales: {
                y: {
                    beginAtZero: true,
                    precision: 0
                }
            }
        }
    });
</script>

<?php require_once __DIR__ . "/INCLUDE/footer.php"; ?>