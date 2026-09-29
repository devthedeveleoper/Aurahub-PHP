<?php
/**
 * Front Controller
 */
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../inc/bootstrap.php'; // Keep bootstrap for now until fully decoupled

// Simple Autoloader (Case-sensitive Linux fix)
spl_autoload_register(function ($class) {
    $root = dirname(__DIR__);
    $path = str_replace('\\', '/', $class);
    
    // Map namespaces to exact folder names for Linux compatibility
    if (strpos($path, 'Core/') === 0) {
        $path = 'core/' . substr($path, 5);
    } elseif (strpos($path, 'App/') === 0) {
        $path = 'app/' . substr($path, 4);
    }
    
    $file = $root . '/' . $path . '.php';
    if (is_readable($file)) {
        require $file;
    }
});

// Setup Router
$router = new Core\Router();

// Add routes
$router->add('', ['controller' => 'HomeController', 'action' => 'index']);
$router->add('watch', ['controller' => 'VideoController', 'action' => 'watch']);
$router->add('upload', ['controller' => 'UploadController', 'action' => 'index']);
$router->add('edit_video', ['controller' => 'VideoController', 'action' => 'edit']);
$router->add('report', ['controller' => 'VideoController', 'action' => 'report']);
$router->add('status', ['controller' => 'VideoController', 'action' => 'status']);
$router->add('playlists', ['controller' => 'PlaylistController', 'action' => 'index']);
$router->add('playlist', ['controller' => 'PlaylistController', 'action' => 'viewPlaylist']);
$router->add('login', ['controller' => 'AuthController', 'action' => 'login']);
$router->add('register', ['controller' => 'AuthController', 'action' => 'register']);
$router->add('logout', ['controller' => 'AuthController', 'action' => 'logout']);
$router->add('account', ['controller' => 'AccountController', 'action' => 'index']);
$router->add('history', ['controller' => 'AccountController', 'action' => 'history']);
$router->add('watch_later', ['controller' => 'AccountController', 'action' => 'watchLater']);
$router->add('analytics', ['controller' => 'AccountController', 'action' => 'analytics']);
$router->add('my_reports', ['controller' => 'AccountController', 'action' => 'reports']);
$router->add('channel', ['controller' => 'AccountController', 'action' => 'channel']);
$router->add('admin_users', ['controller' => 'AdminController', 'action' => 'users']);
$router->add('moderation', ['controller' => 'AdminController', 'action' => 'moderation']);
$router->add('{controller}/{action}');

// Dispatch the current URL
$url = $_SERVER['REQUEST_URI'];
// Basic strip of base path for testing in XAMPP
$basePath = '/aurahub/public/';
if (strpos($url, $basePath) === 0) {
    $url = substr($url, strlen($basePath));
} else {
    $url = parse_url($url, PHP_URL_PATH);
    $url = trim(str_replace('/aurahub/', '', $url), '/');
}

try {
    $router->dispatch($url);
} catch (\Exception $e) {
    echo $e->getMessage();
}
