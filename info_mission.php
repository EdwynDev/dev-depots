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

// Récupération des informations de base
$isChef = $mainController->checkIfIsChefs($userId);
$isRef = $mainController->checkIfIsRef($userId);
$userHasMission = $mainController->hasActiveMission($userId);

// Vérification de l'ID de mission dans l'URL
if (!isset($_GET['id'])) {
    header("Location: mission.php");
    exit;
}

$missionId = (int)$_GET['id'];
$mission = $mainController->getMissionById($missionId);
$missionName = $mission['name'];
$activeMissions = $mainController->hasActiveMission($userId);
$missionDeadline = new DateTime($mission['deadline']);
$now = new DateTime();

// Vérification que la mission existe
if (!$mission) {
    header("Location: mission.php");
    exit;
}

// Gestion de l'acceptation de mission
if (isset($_POST['accept_mission'])) {

    if ($mission['status'] === 'assigned') {
        $_SESSION['error'] = "Cette mission est déjà prise par un autre utilisateur.";
    } elseif ($missionDeadline < $now) {
        $_SESSION['error'] = "La date limite de cette mission est dépassée.";
    } elseif ($activeMissions >= 3) {
        $_SESSION['error'] = "Vous ne pouvez pas avoir plus de 3 missions actives simultanément.";
    } else {
        $accepted = $mainController->acceptMission($missionId, $userId, $missionName);
        if (!$accepted) {
            $_SESSION['error'] = "Vous ne pouvez pas prendre deux fois la même mission.";
        } else if ($accepted) {
            try {
                $webhookUrl = 'https://discord.com/api/webhooks/1330894000034680893/1f9DKJQFHleYb9B_3ZTnlxOl33dMqW8hZOKmQPpZR2vDdfsRLpvZzLTz01Z45Rv7xn5R';
                $deadlineDate = new DateTime($mission['deadline']);
                $deadlineTimestamp = $deadlineDate->getTimestamp();

                $avatarUrl = $_SESSION['discord_user']['avatar'] ?? 'https://neopolyworks.fr/INCLUDE/pp.png';
                $bannerUrl = $_SESSION['discord_user']['banner_url'] ?? 'https://neopolyworks.fr/INCLUDE/banner.png';
                $username = $_SESSION['discord_user']['username'] ?? 'Utilisateur inconnu';

                $messageContent = sprintf(
                    "> <@%s> a accepté une mission !\n" .
                        "Date limite : <t:%d:d> à <t:%d:T>\n" .
                        "> Temps restant : <t:%d:R>",
                    $userId,
                    $deadlineTimestamp,
                    $deadlineTimestamp,
                    $deadlineTimestamp
                );

                $webhookData = [
                    'content' => $messageContent,
                    'embeds' => [[
                        'title' => $mission['name'],
                        'description' => '',
                        'color' => hexdec('00FF00'),
                        'thumbnail' => ['url' => $avatarUrl],
                        'image' => ['url' => $bannerUrl],
                        'fields' => [
                            ['name' => 'Domaine', 'value' => $mainController->getDomainName($mission['domaine_id']), 'inline' => true],
                            ['name' => 'Assigné à', 'value' => $username, 'inline' => true]
                        ]
                    ]]
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

            header("Location: mission.php");
            exit;
        }
    }
}

// Traitement de l'ajout de commentaire
if (isset($_POST['add_comment']) && ($isChef || $isRef)) {
    $comment = trim($_POST['comment']);
    if (!empty($comment) && $mission['status'] !== 'available') {
        $commentId = $mainController->addComment($missionId, $userId, $comment);

        if ($commentId && $mission['assignee_id']) {
            // Envoi du webhook Discord pour le nouveau commentaire
            try {
                $webhookUrl = 'https://discord.com/api/webhooks/1333807414763323392/3__KKc4k-GqalJZJ1a4DBGUH2aJq--PCH32ero9RfDHGx2zMsFOVpH-iDBf3NXRTNc6s';
                $commentAuthor = $_SESSION['discord_user']['username'] ?? 'Un chef';

                $messageContent = sprintf(
                    "> <@%s>, vous avez reçu un nouveau commentaire sur votre mission !\n" .
                        "**De:** %s\n" .
                        "**Mission:** %s\n" .
                        "**Lien:** https://depots.neopolyworks.fr/info_mission.php?id=%d#comment-%d",
                    $mission['assignee_id'],
                    $commentAuthor,
                    $mission['name'],
                    $missionId,
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

        header("Location: info_mission.php?id=" . $missionId);
        exit;
    }
}

// Traitement de la suppression de commentaire (pour les chefs uniquement)
if (isset($_POST['delete_comment']) && $isChef) {
    $commentIdToDelete = (int)$_POST['delete_comment'];
    $mainController->deleteComment($commentIdToDelete);
    header("Location: info_mission.php?id=" . $missionId);
    exit;
}

require_once __DIR__ . "/INCLUDE/header.php";
require_once __DIR__ . '/Parsedown.php';

use Markdown\Parsedown;

$parsedown = new Parsedown();

$attachedFiles = json_decode($mission['attached_file'], true);
?>
<style>
    .markdown-content p {
        margin-bottom: 1rem !important;
        line-height: 1.5 !important;
    }

    .markdown-content strong {
        font-weight: 700 !important;
        color: inherit !important;
    }

    .markdown-content em {
        font-style: italic !important;
    }

    .markdown-content h1 {
        font-size: 2.25rem !important;
        font-weight: 800 !important;
        margin-bottom: 1rem !important;
        margin-top: 2rem !important;
        color: inherit !important;
        border-bottom: 1px solid rgba(255, 255, 255, 0.1) !important;
        padding-bottom: 0.5rem !important;
    }

    .markdown-content h2 {
        font-size: 1.75rem !important;
        font-weight: 700 !important;
        margin-bottom: 0.875rem !important;
        margin-top: 1.75rem !important;
        color: inherit !important;
        border-bottom: 1px solid rgba(255, 255, 255, 0.1) !important;
        padding-bottom: 0.25rem !important;
    }

    .markdown-content h3 {
        font-size: 1.5rem !important;
        font-weight: 700 !important;
        margin-bottom: 0.75rem !important;
        margin-top: 1.5rem !important;
        color: inherit !important;
    }

    .markdown-content h4 {
        font-size: 1.25rem !important;
        font-weight: 600 !important;
        margin-bottom: 0.5rem !important;
        margin-top: 1.25rem !important;
        color: inherit !important;
    }

    .markdown-content ul,
    .markdown-content ol {
        padding-left: 1.5rem !important;
        margin-bottom: 1rem !important;
    }

    .markdown-content ul {
        list-style-type: disc !important;
    }

    .markdown-content ol {
        list-style-type: decimal !important;
    }

    .markdown-content li {
        margin-bottom: 0.5rem !important;
        line-height: 1.5 !important;
    }

    .markdown-content hr {
        border-top: 1px solid currentColor !important;
        margin: 1.5rem 0 !important;
        opacity: 0.3 !important;
    }
</style>
<a href="javascript:history.back()" class="inline-block px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-colors mb-4">← Retour aux missions</a>

<?php if (isset($_SESSION['error'])): ?>
    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg mb-4">
        <?php
        echo htmlspecialchars($_SESSION['error']);
        unset($_SESSION['error']);
        ?>
    </div>
<?php endif; ?>

<?php if (isset($_SESSION['success'])): ?>
    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg mb-4">
        <?php
        echo htmlspecialchars($_SESSION['success']);
        unset($_SESSION['success']);
        ?>
    </div>
<?php endif; ?>

<div class="max-w-3xl mx-auto bg-gradient-to-br from-gray-800 to-<?php echo $mission['difficulty'] === 'facile' ? 'green-500' : ($mission['difficulty'] === 'normal' ? 'yellow-500' : 'red-500'); ?> p-6 rounded-lg shadow-lg text-white">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold"><?php echo htmlspecialchars($mission['name']); ?></h1>
        <div class="flex gap-2">
            <?php if ($isChef): ?>
                <a href="edit_mission.php?id=<?php echo $missionId; ?>" class="px-4 py-2 rounded-full text-sm font-bold bg-blue-500 hover:bg-blue-600 transition-colors">
                    ✏️
                </a>
            <?php endif; ?>
            <span class="px-4 py-2 rounded-full text-sm font-bold <?php echo $mission['difficulty'] === 'facile' ? 'bg-green-500' : ($mission['difficulty'] === 'normal' ? 'bg-yellow-500' : 'bg-red-500'); ?>">
                <?php echo ucfirst(htmlspecialchars($mission['difficulty'])); ?>
            </span>
        </div>
    </div>

    <?php if ($mission['image_url']): ?>
        <?php
        $referenceImages = is_string($mission['image_url'])
            ? json_decode($mission['image_url'], true)
            : $mission['image_url'];

        // Normalize to array if single image
        if (!is_array($referenceImages)) {
            $referenceImages = [$referenceImages];
        }

        if (count($referenceImages) > 0): ?>
            <div class="carousel-container relative w-full overflow-hidden mb-6">
                <div
                    class="carousel-inner flex transition-transform duration-300 ease-in-out"
                    id="carouselInner"
                    style="width: <?php echo count($referenceImages) * 100; ?>%">
                    <?php foreach ($referenceImages as $index => $imageUrl): ?>
                        <div
                            class="carousel-item w-full flex-shrink-0"
                            style="width: <?php echo 100 / count($referenceImages); ?>%">
                            <img
                                src="<?php echo htmlspecialchars($imageUrl); ?>"
                                alt="Reference Image <?php echo $index + 1; ?>"
                                class="w-full h-full object-cover rounded-lg">
                        </div>
                    <?php endforeach; ?>
                </div>

                <?php if (count($referenceImages) > 1): ?>
                    <div class="carousel-controls absolute top-1/2 w-full flex justify-between transform -translate-y-1/2">
                        <button
                            class="carousel-control-prev bg-black/50 text-white p-2 rounded-full ml-2"
                            onclick="changeSlide(-1)">❮</button>
                        <button
                            class="carousel-control-next bg-black/50 text-white p-2 rounded-full mr-2"
                            onclick="changeSlide(1)">❯</button>
                    </div>

                    <div class="carousel-indicators flex justify-center mt-2">
                        <?php foreach ($referenceImages as $index => $imageUrl): ?>
                            <button
                                class="indicator w-3 h-3 rounded-full mx-1 bg-gray-400 indicator-item"
                                onclick="goToSlide(<?php echo $index; ?>)"></button>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <script>
                let currentSlide = 0;
                const carousel = document.getElementById('carouselInner');
                const slides = carousel.getElementsByClassName('carousel-item');
                const indicators = document.getElementsByClassName('indicator-item');

                function updateIndicators() {
                    for (let i = 0; i < indicators.length; i++) {
                        indicators[i].classList.remove('bg-white');
                        indicators[i].classList.add('bg-gray-400');
                    }
                    indicators[currentSlide].classList.remove('bg-gray-400');
                    indicators[currentSlide].classList.add('bg-white');
                }

                function changeSlide(direction) {
                    currentSlide += direction;

                    // Handle wrapping
                    if (currentSlide >= slides.length) {
                        currentSlide = 0;
                    }
                    if (currentSlide < 0) {
                        currentSlide = slides.length - 1;
                    }

                    // Move the carousel
                    carousel.style.transform = `translateX(-${currentSlide * (100 / slides.length)}%)`;
                    updateIndicators();
                }

                function goToSlide(index) {
                    currentSlide = index;
                    carousel.style.transform = `translateX(-${currentSlide * (100 / slides.length)}%)`;
                    updateIndicators();
                }

                // Initialize first indicator
                if (indicators.length > 0) {
                    updateIndicators();
                }
            </script>
        <?php endif; ?>
    <?php endif; ?>
    <?php if (!empty($attachedFiles)): ?>
        <div class="grid grid-cols-1 gap-4 mb-6">
            <div class="bg-gray-700 p-4 rounded-lg mt-6">
                <span class="text-sm text-gray-300">Fichiers joints :</span>
                <p class="text-lg flex flex-wrap gap-6 justify-center">
                    <?php foreach ($attachedFiles as $filePath): ?>
                        <?php
                        // Extraire le nom de fichier à partir du chemin complet
                        $fileName = basename($filePath);
                        ?>
                        <a href="<?php echo htmlspecialchars($filePath); ?>" download class="text-blue-400 hover:underline block mt-2 rounded-lg bg-gray-800 p-2">
                            Télécharger <?php echo htmlspecialchars($fileName); ?>
                        </a>
                    <?php endforeach; ?>
                </p>
            </div>
        </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div class="bg-gray-700 p-4 rounded-lg">
            <span class="text-sm text-gray-300">Date limite</span>
            <p class="text-lg"><?php echo date('d/m/Y H:i', strtotime($mission['deadline'])); ?></p>
        </div>
        <div class="bg-gray-700 p-4 rounded-lg">
            <span class="text-sm text-gray-300">Statut</span>
            <p class="text-lg">
                <?php
                if ($mission['status'] === 'available') {
                    echo 'Disponible';
                } elseif ($mission['status'] === 'assigned') {
                    $assigneeName = $mainController->getUsername($mission['assignee_id']);
                ?>
                    Assignée à <a href="info_user.php?username=<?php echo htmlspecialchars($assigneeName); ?>" class="text-blue-400 hover:underline"><?php echo htmlspecialchars($assigneeName); ?></a>
                <?php
                } elseif ($mission['status'] === 'completed') {
                    $assigneeName = $mainController->getUsername($mission['assignee_id']);
                ?>
                    Fini par <a href="info_user.php?username=<?php echo htmlspecialchars($assigneeName); ?>" class="text-blue-400 hover:underline"><?php echo htmlspecialchars($assigneeName); ?></a>
                <?php
                };
                ?>
            </p>
        </div>
        <div class="bg-gray-700 p-4 rounded-lg">
            <span class="text-sm text-gray-300">Type de fichier attendu</span>
            <p class="text-lg">
                <?php echo str_contains($mission['typeFichier'], '.zip') ?
                    htmlspecialchars($mission['typeFichier']) :
                    '.zip, ' . strtolower(htmlspecialchars($mission['typeFichier']));
                ?>
            </p>
        </div>
    </div>

    <div class="markdown-content bg-gray-900 p-4 rounded-lg overflow-x-auto">
        <?php echo $parsedown->text($mission['description']); ?>
    </div>

    <form method="post" action="" class="mt-6 <?php
                                                echo $mission['status'] === 'assigned' ? 'hidden' : (
                                                    $missionDeadline < $now ? 'hidden' : (
                                                        $activeMissions >= 3 ? 'hidden' : ''
                                                    )
                                                );
                                                ?>">
        <button type="submit"
            name="accept_mission"
            class="w-full bg-green-500 text-white py-3 rounded-lg hover:bg-green-600 transition-colors">
            Accepter la mission
        </button>
    </form>
</div>

<?php
$canViewComments = $isChef || $isRef || ($mission['assignee_id'] === $userId);

if ($canViewComments): ?>
    <div class="max-w-3xl mx-auto mt-8 bg-gradient-to-br from-gray-800 to-<?php echo $mission['difficulty'] === 'facile' ? 'green-500' : ($mission['difficulty'] === 'normal' ? 'yellow-500' : 'red-500'); ?> p-6 rounded-lg shadow-lg text-white">
        <h3 class="text-2xl font-bold text-center mb-6">Commentaires des chefs</h3>

        <?php if ($isChef || $isRef): ?>
            <form method="post" action="" class="mb-6">
                <textarea
                    name="comment"
                    class="w-full bg-gray-700 text-white p-4 rounded-lg resize-y min-h-[100px]"
                    placeholder="Ajouter un commentaire..."
                    required></textarea>
                <button type="submit" name="add_comment" class="w-full bg-green-500 text-white py-3 rounded-lg hover:bg-green-600 transition-colors mt-4">
                    Publier le commentaire
                </button>
            </form>
        <?php endif; ?>

        <div class="space-y-4">
            <?php
            $comments = $mainController->getComments($missionId);
            foreach ($comments as $comment):
            ?>
                <div class="bg-gray-700 p-4 rounded-lg" id="comment-<?php echo $comment['id']; ?>">
                    <div class="flex justify-between items-center mb-2">
                        <span class="font-bold text-blue-400">
                            <?php echo htmlspecialchars($comment['username']); ?>
                        </span>
                        <span class="text-sm text-gray-300">
                            <?php echo date('d/m/Y à H:i', strtotime($comment['created_at'])); ?>
                        </span>
                        <?php if ($isChef): ?>
                            <form method="post" action="" style="display:inline;">
                                <input type="hidden" name="delete_comment" value="<?php echo $comment['id']; ?>">
                                <button type="submit" class="ml-2 px-2 py-1 bg-red-600 text-white rounded hover:bg-red-700 text-xs" onclick="return confirm('Supprimer ce commentaire ?');">
                                    Supprimer
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                    <div class="text-gray-200">
                        <?php echo nl2br(htmlspecialchars($comment['content'])); ?>
                    </div>
                    <?php if ($mission['assignee_id'] === $userId): ?>
                        <div class="flex gap-2 mt-2">
                            <?php
                            $reactions = $mainController->getReactions($comment['id']);
                            $userReactions = $mainController->getUserReactions($comment['id'], $userId);
                            ?>
                            <button type="button"
                                class="reaction-btn px-3 py-1 border border-gray-500 rounded-full text-sm <?php echo in_array('like', $userReactions) ? 'bg-green-500 border-green-500' : 'hover:bg-gray-600'; ?>"
                                data-comment-id="<?php echo $comment['id']; ?>"
                                data-reaction="like">
                                👍 <span class="reaction-count"><?php echo $reactions['like']; ?></span>
                            </button>
                            <button type="button"
                                class="reaction-btn px-3 py-1 border border-gray-500 rounded-full text-sm <?php echo in_array('heart', $userReactions) ? 'bg-red-500 border-red-500' : 'hover:bg-gray-600'; ?>"
                                data-comment-id="<?php echo $comment['id']; ?>"
                                data-reaction="heart">
                                ❤️ <span class="reaction-count"><?php echo $reactions['heart']; ?></span>
                            </button>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <script>
        document.querySelectorAll('.reaction-btn').forEach(button => {
            button.addEventListener('click', async function() {
                const commentId = this.dataset.commentId;
                const reactionType = this.dataset.reaction;

                try {
                    const response = await fetch('handle_reaction.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `comment_id=${commentId}&reaction_type=${reactionType}`
                    });

                    const data = await response.json();
                    if (data.success) {
                        this.classList.toggle('active');
                        const count = this.querySelector('.reaction-count');
                        const currentCount = parseInt(count.textContent);
                        count.textContent = this.classList.contains('active') ? currentCount + 1 : currentCount - 1;
                    }
                } catch (error) {
                    console.error('Erreur:', error);
                }
            });
        });
    </script>
<?php endif; ?>

<?php require_once __DIR__ . "/INCLUDE/footer.php"; ?>