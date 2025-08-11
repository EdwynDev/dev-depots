<?php
if (session_status() == PHP_SESSION_NONE) {
    session_set_cookie_params(30 * 24 * 60 * 60);
    session_start();
}

if (!isset($_SESSION['discord_user'])) {
    header("Location: https://depots.neopolyworks.fr");
    exit;
}

// Remove message count limitation logic
if (!isset($_SESSION['thread_id_loutre'])) {
    $ch = curl_init('https://api.openai.com/v1/threads');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer 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',
        'OpenAI-Beta: assistants=v2'
    ]);
    curl_setopt($ch, CURLOPT_POST, true);
    $response = curl_exec($ch);
    curl_close($ch);

    $threadData = json_decode($response, true);
    if (isset($threadData['id'])) {
        $_SESSION['thread_id_loutre'] = $threadData['id'];
    } else {
        echo json_encode(['error' => 'Erreur lors de la création du thread']);
        exit;
    }
}

require __DIR__ . '/Parsedown.php';

use Markdown\Parsedown;

$parsedown = new Parsedown();
$currentText = '';
function addMessageToHistory($role, $content)
{
    global $parsedown;
    $currentText = $parsedown->text($content);
    $_SESSION['chat_history_loutre'][] = ['role' => $role, 'content' => $currentText];
    if (count($_SESSION['chat_history_loutre']) > 30) {
        array_shift($_SESSION['chat_history_loutre']);
    }
}

// Traitement des messages POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['message'])) {
    $userMessage = trim($_POST['message']);
    if (empty($userMessage)) {
        echo json_encode(['error' => 'Le message ne peut pas être vide.']);
        exit;
    }

    // Remove message count increment

    // Ajouter le message au thread
    $ch = curl_init("https://api.openai.com/v1/threads/{$_SESSION['thread_id_loutre']}/messages");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer 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',
        'OpenAI-Beta: assistants=v2'
    ]);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
        'role' => 'user',
        'content' => $userMessage
    ]));
    $response = curl_exec($ch);
    curl_close($ch);

    // Exécuter l'assistant sur le thread
    $ch = curl_init("https://api.openai.com/v1/threads/{$_SESSION['thread_id_loutre']}/runs");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer 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',
        'OpenAI-Beta: assistants=v2'
    ]);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
        'assistant_id' => 'asst_XzOdnhvwb5iZ8IZxu300AgQE'
    ]));
    $runResponse = curl_exec($ch);
    curl_close($ch);

    $runData = json_decode($runResponse, true);
    if (!isset($runData['id'])) {
        echo json_encode(['error' => 'Erreur lors de la création du run']);
        exit;
    }

    if (!waitForRunCompletion($_SESSION['thread_id_loutre'], $runData['id'], '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')) {
        echo json_encode(['error' => 'Timeout lors de l\'attente de la réponse']);
        exit;
    }

    $ch = curl_init("https://api.openai.com/v1/threads/{$_SESSION['thread_id_loutre']}/messages");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer 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',
        'OpenAI-Beta: assistants=v2'
    ]);
    $response = curl_exec($ch);
    curl_close($ch);

    $responseData = json_decode($response, true);
    if (isset($responseData['data']) && !empty($responseData['data'])) {
        $assistantMessage = $responseData['data'][0]['content'][0]['text']['value'];
        addMessageToHistory('user', $userMessage);
        addMessageToHistory('assistant', $assistantMessage);
        echo json_encode(['message' => $assistantMessage]);
    } else {
        echo json_encode(['error' => 'Aucune réponse reçue']);
    }
    exit;
}

function waitForRunCompletion($threadId, $runId, $apiKey)
{
    // Désactiver la limite de temps d'exécution du script PHP
    set_time_limit(0);

    while (true) {
        $ch = curl_init("https://api.openai.com/v1/threads/$threadId/runs/$runId");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $apiKey,
            'OpenAI-Beta: assistants=v2'
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 0); // Désactive le timeout de cURL

        $response = curl_exec($ch);

        if (curl_errno($ch)) {
            echo json_encode(['error' => 'Erreur cURL : ' . curl_error($ch)]);
            curl_close($ch);
            return false;
        }

        curl_close($ch);

        $runStatus = json_decode($response, true);

        if ($runStatus['status'] === 'completed') {
            return true; // La réponse est prête
        }

        if (in_array($runStatus['status'], ['failed', 'expired', 'cancelled'])) {
            return false; // Erreur ou annulation
        }

        // Attendre 2 secondes avant de vérifier à nouveau
        sleep(2);
    }
}
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="/INCLUDE/chatbot.png" type="image/png">

    <meta property="og:title" content="NEO POLY WORKS - DEPOTS | AI BOT ASSISTANCE">
    <meta property="og:description" content="AI Helper de la Plateforme de gestion de missions et de dépôts de fichiers pour les participants au projet NEO POLY WORKS. Réponds à toute vos question concernant le dépots.">
    <meta property="og:image" content="/INCLUDE/chatbot.png">
    <meta property="og:url" content="https://devdepots.neopolyworks.fr/chatbot-fullscreen.php">
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="NEO POLY WORKS - DEPOTS | AI BOT ASSISTANCE">

    <meta name="twitter:card" content="summary">
    <meta name="twitter:title" content="NEO POLY WORKS - DEPOTS | AI BOT ASSISTANCE">
    <meta name="twitter:description" content="AI Helper de la Plateforme de gestion de missions et de dépôts de fichiers pour les participants au projet NEO POLY WORKS. Réponds à toute vos question concernant le dépots.">
    <meta name="twitter:image" content="/INCLUDE/chatbot.png">

    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <title>Chatbot</title>
    <style>
        @font-face {
            font-family: 'Exo2-Regular';
            src: url('../FONTS/Exo2-Regular.otf') format('opentype');
        }

        * {
            box-sizing: border-box;
            font-family: 'Exo2-Regular', sans-serif;
        }

        @keyframes pulse {

            0%,
            100% {
                opacity: 1;
            }

            50% {
                opacity: 0.5;
            }
        }

        .animate-pulse {
            animation: pulse 1.5s infinite;
        }

        #chatbot-container {
            width: 100% !important;
            height: 100% !important;
        }

        #chatbot-window,
        #chatbot-messages {
            height: 100% !important;
            max-height: 100% !important;
            width: 100% !important;
            max-width: 100% !important;
            overflow: auto !important;
        }

        #chatbot-messages::-webkit-scrollbar {
            width: 8px;
        }

        #chatbot-messages::-webkit-scrollbar-thumb {
            background-color: #111827;
            border-radius: 4px;
        }

        #chatbot-messages::-webkit-scrollbar-track {
            background-color: rgb(255, 255, 255);
        }

        #chatbot-messages ul,
        #chatbot-messages ol {
            margin: 10px 0;
            padding-left: 20px;
        }

        #chatbot-messages li {
            margin: 5px 0;
        }

        #chatbot-messages a {
            color: #1e90ff;
            text-decoration: underline;
        }

        #chatbot-messages strong {
            font-weight: bold;
        }

        #chatbot-messages em {
            font-style: italic;
        }
    </style>
</head>

<body>
    <div id="chatbot-container" class="fixed">
        <div id="chatbot-window" class="bg-gray-900 rounded-xl shadow-2xl w-96 h-[500px] flex flex-col transform transition-all duration-300 ease-in-out">
            <div class="flex items-center justify-between p-4 bg-gray-800 rounded-t-xl">
                <div class="flex items-center">
                    <img src="/INCLUDE/chatbot.png" alt="Logo" class="w-8 h-8 rounded-full">
                    <span class="ml-2 text-white font-bold text-lg">Loutre | AI CHAT</span>
                </div>
            </div>

            <div class="flex justify-between p-2 bg-gray-800">
                <button id="clear-chat" class="px-3 py-1 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors">
                    Supprimer le chat
                </button>
                <div class="flex space-x-2">
                    <button id="back" class="px-3 py-1 bg-blue-800 text-white rounded-lg hover:bg-blue-700 transition-colors">
                        Retour
                    </button>
                    <button id="scroll-to-top" class="px-3 py-1 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                        Haut
                    </button>
                    <button id="scroll-to-bottom" class="px-3 py-1 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                        Bas
                    </button>
                </div>
            </div>

            <div id="chatbot-messages" class="flex-1 p-4 overflow-y-auto space-y-4 scrollbar-thin scrollbar-thumb-gray-700 scrollbar-track-gray-900">
                <?php foreach ($_SESSION['chat_history_loutre'] as $msg): ?>
                    <div class="<?php echo $msg['role'] === 'user' ? 'text-right' : 'text-left'; ?>">
                        <div class="<?php echo $msg['role'] === 'user' ? 'bg-blue-600 text-white' : 'bg-gray-700 text-white'; ?> inline-block px-4 py-2 rounded-lg max-w-[80%] break-words">
                            <?php echo $msg['content']; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div id="typing-indicator" class="text-left hidden p-4">
                <div class="bg-gray-700 text-white inline-block px-4 py-2 rounded-lg max-w-[80%]">
                    <span class="flex items-center">
                        <span class="animate-pulse">l'Assistant écrit ...</span>
                    </span>
                </div>
            </div>

            <div class="p-4 bg-gray-800 rounded-b-xl">
                <div class="flex items-center space-x-2">
                    <textarea
                        id="chatbot-input"
                        rows="1"
                        class="flex-1 px-4 py-2 rounded-lg bg-gray-700 text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 border border-gray-600 resize-none overflow-hidden"
                        placeholder="Tapez un message..."></textarea>
                    <button id="send-message"
                        class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const messagesDiv = document.getElementById('chatbot-messages');
            messagesDiv.scrollTop = messagesDiv.scrollHeight;

            document.getElementById('clear-chat').addEventListener('click', function() {
                messagesDiv.innerHTML = '';
                fetch('/INCLUDE/clear_chat.php', {
                        method: 'POST'
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            console.log('Chat supprimé');
                        } else {
                            console.error('Erreur lors de la suppression du chat');
                        }
                    })
                    .catch(error => console.error('Erreur fetch :', error));
            });

            document.getElementById('back').addEventListener('click', function() {
                window.open('https://devdepots.neopolyworks.fr/');
            });

            document.getElementById('scroll-to-top').addEventListener('click', function() {
                messagesDiv.scrollTop = 0;
            });

            document.getElementById('scroll-to-bottom').addEventListener('click', function() {
                messagesDiv.scrollTop = messagesDiv.scrollHeight;
            });

            const markdownScript = document.createElement('script');
            markdownScript.src = 'https://cdnjs.cloudflare.com/ajax/libs/marked/4.0.2/marked.min.js';
            document.head.appendChild(markdownScript);

            markdownScript.onload = function() {
                marked.setOptions({
                    breaks: true,
                    gfm: true
                });
            };

            function sendMessage() {
                const input = document.getElementById('chatbot-input');
                const sendButton = document.getElementById('send-message');
                const message = input.value.trim();

                if (message) {
                    // Désactiver le champ de saisie et le bouton d'envoi
                    input.disabled = true;
                    sendButton.disabled = true;

                    messagesDiv.innerHTML += `
                <div class="text-right">
                    <div class="bg-blue-600 text-white inline-block px-4 py-2 rounded-lg max-w-[80%] break-words">
                        ${message}
                    </div>
                </div>
            `;
                    messagesDiv.scrollTop = messagesDiv.scrollHeight;

                    const typingIndicator = document.getElementById('typing-indicator');
                    typingIndicator.classList.remove('hidden');

                    fetch('loutre-chatbot.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded',
                            },
                            body: `message=${encodeURIComponent(message)}`
                        })
                        .then(response => response.json())
                        .then(data => {
                            messagesDiv.scrollTop = messagesDiv.scrollHeight;
                            typingIndicator.classList.add('hidden');

                            if (data.error) {
                                console.error(data.error);
                                return;
                            }

                            const parsedMessage = marked.parse(data.message);

                            messagesDiv.innerHTML += `
                    <div class="text-left">
                        <div class="bg-gray-700 text-white inline-block px-4 py-2 rounded-lg max-w-[80%] break-words">
                            ${parsedMessage}
                        </div>
                    </div>
                `;
                            messagesDiv.scrollTop = messagesDiv.scrollHeight;

                            // Réactiver le champ de saisie et le bouton d'envoi
                            input.disabled = false;
                            sendButton.disabled = false;
                        })
                        .catch(error => {
                            console.error('Erreur fetch :', error);
                            typingIndicator.classList.add('hidden');
                            input.disabled = false;
                            sendButton.disabled = false;
                        });

                    input.value = '';
                }
            }

            document.getElementById('send-message').addEventListener('click', sendMessage);

            document.getElementById('chatbot-input').addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    sendMessage();
                }
            });
        });
    </script>
</body>

</html>