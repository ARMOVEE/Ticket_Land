<?php

declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (isset($_SERVER['SERVER_PORT']) && (int) $_SERVER['SERVER_PORT'] === 443);
    session_set_cookie_params([
        'httponly' => true,
        'samesite' => 'Lax',
        'secure'   => $isHttps,
    ]);
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
use App\Controllers\AdminController;
use App\Controllers\StaffController;
use App\Controllers\CancellationController;

// Direct fallback for event poster webp files
$reqUri = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?? '';
if (preg_match('#^/([a-zA-Z0-9_-]+\.webp)$#', $reqUri, $webpMatch)) {
    $candPath = __DIR__ . '/assets/img/events/' . $webpMatch[1];
    if (is_file($candPath)) {
        header('Content-Type: image/webp');
        header('Content-Length: ' . (string) filesize($candPath));
        readfile($candPath);
        exit;
    }
}

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
$router->post('/api/checkout/update-qty', [CheckoutController::class, 'updateQty']);
$router->post('/api/checkout/extend-payment-session', [CheckoutController::class, 'extendPaymentSession']);
$router->post('/api/checkout/pay', [CheckoutController::class, 'pay']);

// Contact Route
$router->post('/api/contact', [ContactController::class, 'send']);

// Batalkan Pesanan Routes (Fase 3)
$router->get('/batalkan', [CancellationController::class, 'index']);
$router->post('/batalkan', [CancellationController::class, 'submit']);

// Dashboard Admin Routes (Langkah 2)
$router->get('/admin', [AdminController::class, 'index']);
$router->get('/admin/login', [AdminController::class, 'index']);
$router->get('/admin/logout', [AdminController::class, 'logout']);
$router->get('/admin/reports/export-csv', [AdminController::class, 'exportCsv']);
$router->get('/admin/{section}', [AdminController::class, 'index']);

$router->get('/api/admin/state', [AdminController::class, 'state']);
$router->post('/api/admin/login', [AdminController::class, 'login']);
$router->post('/api/admin/logout', [AdminController::class, 'logout']);
$router->post('/api/admin/events/save', [AdminController::class, 'saveEvent']);
$router->post('/api/admin/events/delete', [AdminController::class, 'deleteEvent']);
$router->post('/api/admin/events/restore', [AdminController::class, 'restoreEvent']);
$router->post('/api/admin/categories/save', [AdminController::class, 'saveCategory']);
$router->post('/api/admin/categories/delete', [AdminController::class, 'deleteCategory']);
$router->post('/api/admin/transactions/create', [AdminController::class, 'createTransaction']);
$router->post('/api/admin/users/create', [AdminController::class, 'createUser']);

// Staff Routes (Langkah 3)
$router->get('/staff', [StaffController::class, 'index']);
$router->get('/staff/scan', [StaffController::class, 'index']);
$router->get('/staff/login', [StaffController::class, 'loginPage']);
$router->get('/staff/logout', [StaffController::class, 'logout']);

$router->post('/api/staff/login', [StaffController::class, 'login']);
$router->post('/api/staff/logout', [StaffController::class, 'logout']);
$router->post('/api/staff/check-ticket', [StaffController::class, 'checkTicket']);
$router->post('/api/staff/confirm-checkin', [StaffController::class, 'confirmCheckin']);
$router->post('/api/staff/reject-ktp', [StaffController::class, 'rejectKtp']);

$router->dispatch($_SERVER['REQUEST_METHOD'] ?? 'GET', $_SERVER['REQUEST_URI'] ?? '/');
