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
use App\Controllers\CustomerAuthController;
use App\Controllers\CheckoutController;
use App\Controllers\ContactController;

$router = new Router();

// Landing & Catalog Routes
$router->get('/', [HomeController::class, 'index']);
$router->get('/health', [HomeController::class, 'health']);
$router->get('/api/events', [HomeController::class, 'eventsApi']);

// Customer Auth Routes (Optional login/signup)
$router->post('/api/customer/register', [CustomerAuthController::class, 'register']);
$router->post('/api/customer/login', [CustomerAuthController::class, 'login']);
$router->post('/api/customer/logout', [CustomerAuthController::class, 'logout']);

// Checkout 4-Step Routes (Guest & Logged-in User)
$router->post('/api/checkout/start', [CheckoutController::class, 'startSession']);
$router->post('/api/checkout/reset', [CheckoutController::class, 'resetSession']);
$router->post('/api/checkout/pay', [CheckoutController::class, 'pay']);

// Contact Route
$router->post('/api/contact', [ContactController::class, 'send']);

$router->dispatch($_SERVER['REQUEST_METHOD'] ?? 'GET', $_SERVER['REQUEST_URI'] ?? '/');
