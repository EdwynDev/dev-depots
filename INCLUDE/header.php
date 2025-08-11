<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="/INCLUDE/favicon.png" type="image/png">
    <title>NEO POLY WORKS - DEPOTS</title>

    <!-- Balises meta pour les embed de lien -->
    <meta property="og:title" content="NEO POLY WORKS - DEPOTS">
    <meta property="og:description" content="Plateforme de gestion de missions et de dépôts de fichiers pour les participants au projet NEO POLY WORKS.">
    <meta property="og:image" content="/INCLUDE/favicon.png">
    <meta property="og:url" content="https://depots.neopolyworks.fr/">
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="NEO POLY WORKS - DEPOTS">

    <meta name="twitter:card" content="summary">
    <meta name="twitter:title" content="NEO POLY WORKS - DEPOTS">
    <meta name="twitter:description" content="Plateforme de gestion de missions et de dépôts de fichiers pour les participants au projet NEO POLY WORKS.">
    <meta name="twitter:image" content="/INCLUDE/favicon.png">

    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.11.3/css/jquery.dataTables.min.css">
    <script type="text/javascript" charset="utf8" src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script type="text/javascript" charset="utf8" src="https://cdn.datatables.net/1.11.3/js/jquery.dataTables.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <style>
        /* Base styles */
        @font-face {
            font-family: 'Exo2-Regular';
            src: url('../FONTS/Exo2-Regular.otf') format('opentype');
        }

        /* Reset */
        * {
            box-sizing: border-box;
            font-family: 'Exo2-Regular', sans-serif;
            padding: 0;
            margin: 0;
        }

        form select option[value="facile"] {
            color: #4CAF50;
        }

        form select option[value="normal"] {
            color: #FFC107;
        }

        form select option[value="difficile"] {
            color: #FF5722;
        }
    </style>
</head>

<body class="bg-gray-900 text-white">
    <header class="w-full h-20 sticky top-0 z-50 bg-transparent text-gray-200 ">
        <nav class="flex flex-wrap justify-between items-center w-11/12 mx-auto p-4 bg-gray-800 rounded-lg">
            <div class="flex items-center justify-between w-full">
                <a href="https://neopolyworks.fr/" class="flex items-center bg-white p-2 rounded-lg opacity-50 hover:opacity-100">
                    <img src="/INCLUDE/favicon.png" alt="icon" class="h-12">
                </a>

                <!-- Bouton du menu burger pour les petits écrans -->
                <button id="menu-toggle" class="block md:hidden text-gray-200 focus:outline-none">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16m-7 6h7"></path>
                    </svg>
                </button>

                <!-- Menu de navigation -->
                <ul id="nav-links" class="hidden md:flex md:items-center md:space-x-5">
                    <li>
                        <a href="files.php" class="text-gray-200 font-semibold no-underline px-4 py-2 rounded-lg transition-all duration-300 hover:bg-gray-200 hover:text-gray-900 hover:shadow-md">
                            🗃️ Fichier
                        </a>
                    </li>
                    <li>
                        <a href="uploads.php" class="text-gray-200 font-semibold no-underline px-4 py-2 rounded-lg transition-all duration-300 hover:bg-gray-200 hover:text-gray-900 hover:shadow-md">
                            🚀 Uploads
                        </a>
                    </li>
                    <li>
                        <a href="mission.php" class="text-gray-200 font-semibold no-underline px-4 py-2 rounded-lg transition-all duration-300 hover:bg-gray-200 hover:text-gray-900 hover:shadow-md">
                            📇 Mission
                        </a>
                    </li>
                    <li>
                        <a href="missionSecondary.php" class="text-gray-200 font-semibold no-underline px-4 py-2 rounded-lg transition-all duration-300 hover:bg-gray-200 hover:text-gray-900 hover:shadow-md">
                            📇 Mission Secondaire
                        </a>
                    </li>
                    <li>
                        <a href="gallery3d.php" class="text-gray-200 font-semibold no-underline px-4 py-2 rounded-lg transition-all duration-300 hover:bg-gray-200 hover:text-gray-900 hover:shadow-md">
                            🎨 Galerie 3D
                        </a>
                    </li>
                    <li>
                        <a href="gestionDomaine.php" class="text-gray-200 font-semibold no-underline px-4 py-2 rounded-lg transition-all duration-300 hover:bg-gray-200 hover:text-gray-900 hover:shadow-md">
                            ⚙️ Gestion du domaine
                        </a>
                    </li>
                    <!-- <li>
                        <a href="chatbot-fullscreen.php" class="text-gray-200 font-semibold no-underline px-4 py-2 rounded-lg transition-all duration-300 hover:bg-gray-200 hover:text-gray-900 hover:shadow-md">
                            🤖 AI-Assitant
                        </a>
                    </li> -->
                </ul>
            </div>
        </nav>
    </header>

    <a href="faq.php" class="fixed bottom-8 right-8 w-16 h-16 bg-blue-500 rounded-full flex items-center justify-center text-2xl shadow-lg hover:bg-blue-600 transition-colors z-50" title="FAQ">
        ❓
    </a>

    <div class="container mx-auto px-4 py-6">