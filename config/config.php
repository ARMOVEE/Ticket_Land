<?php

declare(strict_types=1);

use App\Core\Env;

require_once dirname(__DIR__) . '/app/core/Env.php';

Env::load(dirname(__DIR__) . '/.env');

date_default_timezone_set('Asia/Jakarta');

return [
    'app_name' => Env::get('APP_NAME', 'Ticket Land'),
    'app_env'  => Env::get('APP_ENV', 'local'),
    'app_url'  => Env::get('APP_URL', 'https://localhost'),
    'db' => [
        'host'    => Env::get('DB_HOST', 'localhost'),
        'port'    => Env::get('DB_PORT', '3306'),
        'name'    => Env::get('DB_NAME', 'db_ticketland'),
        'user'    => Env::get('DB_USER', 'root'),
        'pass'    => Env::get('DB_PASS', ''),
        'charset' => 'utf8mb4',
    ],
    'smtp' => [
        'host'       => Env::get('SMTP_HOST', 'smtp.gmail.com'),
        'port'       => (int) Env::get('SMTP_PORT', '587'),
        'secure'     => Env::get('SMTP_SECURE', 'tls'),
        'user'       => Env::get('SMTP_USER', ''),
        'pass'       => Env::get('SMTP_PASS', ''),
        'from'       => Env::get('SMTP_FROM', ''),
        'from_name'  => Env::get('SMTP_FROM_NAME', 'Ticket Land'),
    ],
];
