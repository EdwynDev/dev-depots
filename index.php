<?php

// Point d'entrée principal avec routeur
require_once __DIR__ . '/app/Router.php';

// Initialiser et exécuter le routeur
$router = new Router();
$router->route();