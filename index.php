<?php
// index.php - Master Web Router
require_once 'app/db.php';
require_once 'app/functions.php'; // sets up session (with fallback path) and starts it

class Router {
    private $routes = [];

    public function add($uri, $file, $requires_auth = true) {
        $this->routes[$uri] = ['file' => $file, 'requires_auth' => $requires_auth];
    }

    public function dispatch($requested_uri) {
        $uri = trim(parse_url($requested_uri, PHP_URL_PATH), '/');
        if ($uri === '') $uri = 'dashboard';

        if (strpos($uri, 'api/connect') === 0) {
            require_once 'api/connect.php';
            exit();
        }
        if (strpos($uri, 'api/tg_api') === 0) {
            require_once 'api/tg_api.php';
            exit();
        }

        if (array_key_exists($uri, $this->routes)) {
            $route = $this->routes[$uri];

            if ($route['requires_auth'] && !isset($_SESSION['user_id'])) {
                header("Location: /login");
                exit();
            }
            if (!$route['requires_auth'] && isset($_SESSION['user_id']) && $uri !== 'logout') {
                header("Location: /dashboard");
                exit();
            }

            global $route_path;
            $route_path = $uri;

            if (file_exists($route['file'])) {
                require_once $route['file'];
            } else {
                $this->abort(500, "Required file missing for route '{$uri}'");
            }
        } else {
            $this->abort(404, "Page Not Found");
        }
    }

    private function abort($code, $message) {
        http_response_code($code);
        echo "<!DOCTYPE html><html><head><title>{$code} Error</title>";
        echo "<style>body{background:#0a0a0f;color:#fff;font-family:sans-serif;display:flex;align-items:center;justify-content:center;height:100vh;margin:0;} .box{text-align:center;background:#15151f;padding:40px;border-radius:15px;border:1px solid #2a2a3a;} h1{color:#7c5cff;margin-top:0;} a{color:#fff;text-decoration:none;background:#7c5cff;padding:10px 20px;border-radius:5px;display:inline-block;margin-top:20px;}</style>";
        echo "</head><body><div class='box'><h1>Error {$code}</h1><p>{$message}</p><a href='/dashboard'>Return to Dashboard</a></div></body></html>";
        exit();
    }
}

$router = new Router();

// PUBLIC ROUTES
$router->add('login',    'auth/login.php',    false);
$router->add('register', 'auth/register.php', false);
$router->add('logout',   'auth/logout.php',   false);
$router->add('forgot',   'auth/forgot.php',   false);

// PROTECTED ROUTES
$router->add('dashboard',       'views/dashboard.php');
$router->add('keys',            'views/keys.php');
$router->add('generate-key',    'views/generate_key.php');
$router->add('team',            'views/team.php');
$router->add('referrals',       'views/referrals.php');
$router->add('settings',        'views/settings.php');
$router->add('server-settings', 'views/server_settings.php');
$router->add('logs',            'views/logs.php');
$router->add('tester',          'views/tester.php');
$router->add('tenants',         'views/tenants.php');

$request_uri = $_GET['uri'] ?? '/';
$router->dispatch($request_uri);
?>
