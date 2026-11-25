<?php

declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$rootDir = dirname(__DIR__);
require_once $rootDir . '/config/config.php';

spl_autoload_register(function (string $class) use ($rootDir): void {
    $prefix = 'App\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }

    $relative = substr($class, strlen($prefix));
    $parts = explode('\\', $relative);
    $fileName = array_pop($parts) . '.php';
    $dirPath = strtolower(implode('/', $parts));

    $file = $rootDir . '/app/' . ($dirPath ? $dirPath . '/' : '') . $fileName;
    if (is_file($file)) {
        require_once $file;
    }
});

use App\Core\Router;
use App\Controllers\HomeController;

$router = new Router();

$router->get('/', [HomeController::class, 'index']);
$router->get('/health', [HomeController::class, 'health']);

$router->dispatch($_SERVER['REQUEST_METHOD'] ?? 'GET', $_SERVER['REQUEST_URI'] ?? '/');
