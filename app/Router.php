<?php

class Router
{
    private $routes = [];
    private $currentRoute = null;

    public function __construct()
    {
        $this->defineRoutes();
    }

    private function defineRoutes()
    {
        $this->routes = [
            '/' => ['controller' => 'HomeController', 'method' => 'index'],
            '/login' => ['controller' => 'AuthController', 'method' => 'login'],
            '/logout' => ['controller' => 'AuthController', 'method' => 'logout'],
            '/dashboard' => ['controller' => 'DashboardController', 'method' => 'index'],
            '/missions' => ['controller' => 'MissionController', 'method' => 'index'],
            '/missions/view/{id}' => ['controller' => 'MissionController', 'method' => 'view'],
            '/missions/create' => ['controller' => 'MissionController', 'method' => 'create'],
            '/missions/edit/{id}' => ['controller' => 'MissionController', 'method' => 'edit'],
            '/missions/secondary' => ['controller' => 'MissionController', 'method' => 'secondary'],
            '/uploads' => ['controller' => 'UploadController', 'method' => 'index'],
            '/files' => ['controller' => 'FileController', 'method' => 'index'],
            '/gallery' => ['controller' => 'GalleryController', 'method' => 'index'],
            '/users/{id}' => ['controller' => 'UserController', 'method' => 'view'],
            '/users/profile/{username}' => ['controller' => 'UserController', 'method' => 'profile'],
            '/admin/domain' => ['controller' => 'AdminController', 'method' => 'domain'],
            '/admin/analytics' => ['controller' => 'AdminController', 'method' => 'analytics'],
            '/faq' => ['controller' => 'FaqController', 'method' => 'index'],
            '/api/reactions' => ['controller' => 'ApiController', 'method' => 'reactions'],
            '/api/comments' => ['controller' => 'ApiController', 'method' => 'comments'],
        ];
    }

    public function getCurrentPath()
    {
        $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        return rtrim($path, '/') ?: '/';
    }

    public function route()
    {
        $currentPath = $this->getCurrentPath();
        $method = $_SERVER['REQUEST_METHOD'];

        foreach ($this->routes as $route => $config) {
            if ($this->matchRoute($route, $currentPath)) {
                $this->currentRoute = $config;
                $this->executeRoute($config, $this->extractParams($route, $currentPath));
                return;
            }
        }

        // Route not found
        $this->executeNotFound();
    }

    private function matchRoute($route, $path)
    {
        $routePattern = preg_replace('/\{[^}]+\}/', '([^/]+)', $route);
        $routePattern = '#^' . $routePattern . '$#';
        return preg_match($routePattern, $path);
    }

    private function extractParams($route, $path)
    {
        $params = [];
        $routeParts = explode('/', trim($route, '/'));
        $pathParts = explode('/', trim($path, '/'));

        foreach ($routeParts as $index => $part) {
            if (preg_match('/\{([^}]+)\}/', $part, $matches)) {
                $params[$matches[1]] = $pathParts[$index] ?? null;
            }
        }

        return $params;
    }

    private function executeRoute($config, $params = [])
    {
        $controllerName = $config['controller'];
        $methodName = $config['method'];

        $controllerFile = __DIR__ . '/Controllers/' . $controllerName . '.php';
        
        if (file_exists($controllerFile)) {
            require_once $controllerFile;
            $controller = new $controllerName();
            
            if (method_exists($controller, $methodName)) {
                $controller->$methodName($params);
                return;
            }
        }

        $this->executeNotFound();
    }

    private function executeNotFound()
    {
        http_response_code(404);
        require_once __DIR__ . '/Views/errors/404.php';
    }
}