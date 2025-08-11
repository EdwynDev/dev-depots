<?php

require_once __DIR__ . '/../../CONTROLLERS/MainController.php';
require_once __DIR__ . '/../../config.php';

use Controllers\MainController;

class BaseController
{
    protected $mainController;
    protected $user = null;
    protected $isChef = false;
    protected $isRef = false;

    public function __construct()
    {
        if (session_status() == PHP_SESSION_NONE) {
            session_set_cookie_params(30 * 24 * 60 * 60);
            session_start();
        }

        $this->mainController = new MainController();
        $this->initializeUser();
    }

    private function initializeUser()
    {
        if (isset($_SESSION['discord_user'])) {
            $userId = $_SESSION['discord_user']['id'];
            $this->user = $this->mainController->checkIfInDatabase($userId);
            
            if ($this->user) {
                $this->isChef = $this->mainController->checkIfIsChefs($userId);
                $this->isRef = $this->mainController->checkIfIsRef($userId);
            }
        }
    }

    protected function requireAuth()
    {
        if (!$this->user) {
            $this->redirect('/login');
            exit;
        }
    }

    protected function requireChef()
    {
        $this->requireAuth();
        if (!$this->isChef) {
            $this->redirect('/dashboard');
            exit;
        }
    }

    protected function render($view, $data = [])
    {
        $data['user'] = $this->user;
        $data['isChef'] = $this->isChef;
        $data['isRef'] = $this->isRef;
        
        extract($data);
        
        ob_start();
        require_once __DIR__ . '/../Views/' . $view . '.php';
        $content = ob_get_clean();
        
        require_once __DIR__ . '/../Views/layouts/main.php';
    }

    protected function renderPartial($view, $data = [])
    {
        extract($data);
        require_once __DIR__ . '/../Views/' . $view . '.php';
    }

    protected function redirect($path)
    {
        header('Location: ' . $path);
        exit;
    }

    protected function json($data)
    {
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
}