<?php

declare(strict_types=1);

use App\Core\Env;

require_once dirname(__DIR__) . '/app/core/Env.php';

Env::load(dirname(__DIR__) . '/.env');

date_default_timezone_set('Asia/Jakarta');

return [
    'app_name' => Env::get('APP_NAME', 'Ticket Land'),
    'app_env'  => Env::get('APP_ENV', 'local'),
    'app_url'  => Env::get('APP_URL', 'https://localhost:8000'),
    'db' => [
        'host'    => Env::get('DB_HOST', 'localhost'),
        'port'    => Env::get('DB_PORT', '3306'),
        'name'    => Env::get('DB_NAME', 'db_ticketland'),
        'user'    => Env::get('DB_USER', 'root'),
        'pass'    => Env::get('DB_PASS', ''),
        'charset' => 'utf8mb4',
    ],
];
