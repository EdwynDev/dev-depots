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
$isChef = $mainController->checkIfIsChefs($userId);
$searchQuery = isset($_GET['search']) ? $_GET['search'] : '';
$selectedDomain = isset($_GET['domain']) ? (int)$_GET['domain'] : null;

// Récupérer tous les domaines pour le select
$domaines = $mainController->giveAllDomaine();

$limit = 10;
$offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;

// Gestion de l'ajout d'une FAQ
if ($isChef && isset($_POST['create_faq'])) {
    $question = $_POST['question'];
    $answer = $_POST['answer'];
    $domaineId = isset($_POST['domaine_id']) ? (int)$_POST['domaine_id'] : null;
    $mainController->createFaq($question, $answer, $userId, $domaineId);
    header('Location: faq.php');
    exit;
}

// Récupération des FAQs avec recherche et pagination
$faqs = $mainController->searchFaqs($searchQuery, $limit, $offset, $selectedDomain);
$totalFaqs = $mainController->countFaqs($searchQuery, $selectedDomain);

require_once __DIR__ . "/INCLUDE/header.php";
?>

<h2 class="text-2xl font-bold mb-4">❓ FAQ - Questions fréquentes</h2>

<div class="text-gray-400 mb-4">
    <?php if (!empty($searchQuery)): ?>
        <?php echo $totalFaqs; ?> résultat(s) trouvé(s) pour "<?php echo htmlspecialchars($searchQuery); ?>"
    <?php else: ?>
        <?php echo $totalFaqs; ?> question(s) au total
    <?php endif; ?>
</div>

<!-- Barre de recherche -->
<form method="GET" action="" class="mb-6">
    <div class="flex gap-2">
        <input type="text" 
               name="search" 
               placeholder="Rechercher une question..." 
               value="<?php echo htmlspecialchars($searchQuery); ?>"
               class="flex-1 px-4 py-2 bg-gray-700 text-white rounded-lg">
        <select name="domain" class="px-4 py-2 bg-gray-700 text-white rounded-lg">
            <option value="">Tous les domaines</option>
            <?php foreach ($domaines as $domaine): ?>
                <option value="<?php echo $domaine['id']; ?>" <?php echo $selectedDomain == $domaine['id'] ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($domaine['name']); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <button type="submit" 
                class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition-colors">
            Rechercher
        </button>
    </div>
</form>

<?php if ($isChef): ?>
    <button onclick="toggleFaqForm()" class="toggle-faq-form bg-gray-700 text-white p-2 rounded-lg hover:bg-gray-600 transition-colors mb-6">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
        </svg>
    </button>

    <div class="create-faq-form flex-col items-center justify-center mt-4 mb-6" style="display: none;">
        <h3 class="text-xl font-bold mb-4">Ajouter une FAQ</h3>
        <form method="post" action="" class="w-full max-w-lg">
            <div class="mb-4">
                <label for="question" class="block text-gray-300 mb-2">Question :</label>
                <input type="text" id="question" name="question" required 
                       class="w-full px-3 py-2 bg-gray-700 text-white rounded-lg">
            </div>

            <div class="mb-4">
                <label for="answer" class="block text-gray-300 mb-2">Réponse :</label>
                <textarea id="answer" name="answer" required rows="4"
                          class="w-full px-3 py-2 bg-gray-700 text-white rounded-lg"></textarea>
            </div>

            <div class="mb-4">
                <label for="domaine_id" class="block text-gray-300 mb-2">Domaine :</label>
                <select id="domaine_id" name="domaine_id" class="w-full px-3 py-2 bg-gray-700 text-white rounded-lg">
                    <?php foreach ($domaines as $domaine): ?>
                        <option value="<?php echo $domaine['id']; ?>">
                            <?php echo htmlspecialchars($domaine['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <button type="submit" name="create_faq" 
                    class="w-full bg-green-500 text-white py-2 rounded-lg hover:bg-green-600 transition-colors">
                Ajouter
            </button>
        </form>
    </div>
<?php endif; ?>

<div class="space-y-4">
    <?php if (empty($faqs)): ?>
        <div class="text-center text-gray-400 py-8">
            Aucune question trouvée.
        </div>
    <?php else: ?>
        <?php foreach ($faqs as $faq): ?>
            <div class="bg-gray-800 rounded-lg overflow-hidden">
                <button class="w-full px-6 py-4 text-left flex justify-between items-center hover:bg-gray-700 transition-colors"
                        onclick="toggleAnswer(<?php echo $faq['id']; ?>)">
                    <h3 class="text-lg font-semibold"><?php echo htmlspecialchars($faq['question']); ?></h3>
                    <svg class="w-6 h-6 transform transition-transform" id="arrow-<?php echo $faq['id']; ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>
                <div class="px-6 py-4 bg-gray-700 hidden" id="answer-<?php echo $faq['id']; ?>">
                    <p class="text-gray-300"><?php echo nl2br(htmlspecialchars($faq['answer'])); ?></p>
                    <div class="mt-2 text-sm text-gray-400">
                        Ajouté le <?php echo date('d/m/Y', strtotime($faq['created_at'])); ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>

        <?php if ($totalFaqs > ($offset + $limit)): ?>
            <div class="text-center mt-6">
                <a href="?search=<?php echo urlencode($searchQuery); ?>&offset=<?php echo $offset + $limit; ?>" 
                   class="inline-block px-6 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition-colors">
                    Voir plus
                </a>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<script>
function toggleFaqForm() {
    const form = document.querySelector('.create-faq-form');
    const button = document.querySelector('.toggle-faq-form');
    const currentDisplay = form.style.display;

    if (currentDisplay === 'none') {
        form.style.display = 'flex';
        button.innerHTML = '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>';
    } else {
        form.style.display = 'none';
        button.innerHTML = '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>';
    }
}

function toggleAnswer(id) {
    const answer = document.getElementById(`answer-${id}`);
    const arrow = document.getElementById(`arrow-${id}`);
    if (answer.classList.contains('hidden')) {
        answer.classList.remove('hidden');
        arrow.classList.add('rotate-180');
    } else {
        answer.classList.add('hidden');
        arrow.classList.remove('rotate-180');
    }
}
</script>

<?php require_once __DIR__ . "/INCLUDE/footer.php"; ?>
