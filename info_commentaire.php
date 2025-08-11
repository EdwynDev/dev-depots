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
$isChef = $mainController->checkIfIsChefs($userId);
if (!$isChef) {
    header("Location: https://depots.neopolyworks.fr");
    exit;
}

$pdo = (new \Config\Database())->connect();
$stmt = $pdo->prepare("
    SELECT 
        c.id AS comment_id,
        c.content,
        c.created_at,
        m.id AS mission_id,
        m.name AS mission_name,
        m.domaine_id,
        m.assignee_id,
        u.username AS assignee_name,
        cu.username AS comment_author
    FROM comments c
    LEFT JOIN missions m ON c.mission_id = m.id
    LEFT JOIN user u ON m.assignee_id = u.userId
    LEFT JOIN user cu ON c.user_id = cu.userId
    ORDER BY c.created_at DESC
");
$stmt->execute();
$comments = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
        vertical-align: top;
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

    .action-btn {
        display: inline-block;
        padding: 0.25rem 0.75rem;
        border-radius: 0.375rem;
        font-size: 0.75rem;
        font-weight: 600;
        margin-bottom: 0.25rem;
        transition: background 0.2s;
    }

    .action-btn.view {
        background: #10b981;
        color: #fff;
    }

    .action-btn.view:hover {
        background: #059669;
    }

    .action-btn.delete {
        background: #ef4444;
        color: #fff;
    }

    .action-btn.delete:hover {
        background: #b91c1c;
    }

    label {
        font-weight: 600;
        color: #d1d5db;
    }

    .dataTables_wrapper {
        overflow-x: hidden;
    }

    @media (max-width: 900px) {

        .dataTables_wrapper .dataTables_length,
        .dataTables_wrapper .dataTables_filter,
        .dataTables_wrapper .dataTables_info,
        .dataTables_wrapper .dataTables_paginate {
            font-size: 0.9rem;
        }

        table.dataTable thead th,
        table.dataTable tbody td {
            padding: 8px !important;
        }
    }
</style>

<h2 class="text-2xl font-bold mb-6">📝 Tous les commentaires de missions</h2>
<div class="overflow-x-auto rounded-lg shadow-lg bg-none">
    <table id="commentsTable" class="min-w-full divide-y divide-gray-700">
        <thead class="bg-gray-700">
            <tr>
                <th class="px-4 py-2 text-left text-sm font-semibold text-gray-300">ID</th>
                <th class="px-4 py-2 text-left text-sm font-semibold text-gray-300">Date</th>
                <th class="px-4 py-2 text-left text-sm font-semibold text-gray-300">Mission</th>
                <th class="px-4 py-2 text-left text-sm font-semibold text-gray-300">Assigné à</th>
                <th class="px-4 py-2 text-left text-sm font-semibold text-gray-300">Auteur</th>
                <th class="px-4 py-2 text-left text-sm font-semibold text-gray-300">Commentaire</th>
                <th class="px-4 py-2 text-left text-sm font-semibold text-gray-300">Actions</th>
            </tr>
        </thead>
        <tbody class="bg-gray-800 divide-y divide-gray-700">
            <?php foreach ($comments as $comment): ?>
                <tr>
                    <td class="px-4 py-2 text-gray-400"><?php echo $comment['comment_id']; ?></td>
                    <td class="px-4 py-2 text-gray-300"><?php echo date('d/m/Y H:i', strtotime($comment['created_at'])); ?></td>
                    <td class="px-4 py-2">
                        <a href="info_mission.php?id=<?php echo $comment['mission_id']; ?>" class="text-blue-400 hover:underline">
                            <?php echo htmlspecialchars($comment['mission_name']); ?>
                        </a>
                    </td>
                    <td class="px-4 py-2">
                        <?php if ($comment['assignee_id']): ?>
                            <a href="info_user.php?id=<?php echo $comment['assignee_id']; ?>" class="text-blue-400 hover:underline">
                                <?php echo htmlspecialchars($comment['assignee_name']); ?>
                            </a>
                        <?php else: ?>
                            <span class="text-gray-400">Non assigné</span>
                        <?php endif; ?>
                    </td>
                    <td class="px-4 py-2">
                        <?php if ($comment['comment_author']): ?>
                            <a href="info_user.php?username=<?php echo urlencode($comment['comment_author']); ?>" class="text-blue-400 hover:underline">
                                <?php echo htmlspecialchars($comment['comment_author']); ?>
                            </a>
                        <?php else: ?>
                            <span class="text-gray-400">Inconnu</span>
                        <?php endif; ?>
                    </td>
                    <td class="px-4 py-2 text-gray-200 max-w-xs break-words"><?php echo nl2br(htmlspecialchars($comment['content'])); ?></td>
                    <td class="px-4 py-2">
                        <a href="info_mission.php?id=<?php echo $comment['mission_id']; ?>#comment-<?php echo $comment['comment_id']; ?>"
                            class="action-btn view mb-1">Voir</a>
                        <?php if ($isChef): ?>
                            <form method="post" action="info_mission.php?id=<?php echo $comment['mission_id']; ?>" style="display:inline;">
                                <input type="hidden" name="delete_comment" value="<?php echo $comment['comment_id']; ?>">
                                <button type="submit" class="action-btn delete"
                                    onclick="return confirm('Supprimer ce commentaire ?');">Supprimer</button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.11.3/js/jquery.dataTables.min.js"></script>
<script>
    $(document).ready(function() {
        $('#commentsTable').DataTable({
            language: {
                url: 'https://cdn.datatables.net/plug-ins/1.11.3/i18n/fr_fr.json'
            },
            dom: '<"flex flex-wrap justify-between items-center mb-4"<"flex"l><"flex"f>>rt<"flex flex-wrap justify-between items-center mt-4"<"flex"i><"flex"p>>',
            pagingType: 'full_numbers',
            lengthMenu: [10, 25, 50, 100],
            pageLength: 25,
            responsive: true,
            order: [
                [1, 'desc']
            ],
            columnDefs: [{
                    targets: [0],
                    visible: false
                },
            ]
        });
    });
</script>
<?php require_once __DIR__ . "/INCLUDE/footer.php"; ?>