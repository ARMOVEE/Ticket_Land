<?php

declare(strict_types=1);

// Support running php -S localhost:8000 directly from the Tiket_Land root directory.
if (PHP_SAPI === 'cli-server') {
    $uriPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
    $publicFile = __DIR__ . '/public' . $uriPath;
    if ($uriPath !== '/' && is_file($publicFile)) {
        return false;
    }
}

require __DIR__ . '/public/index.php';
