<?php

declare(strict_types=1);

if (PHP_SAPI === 'cli-server') {
    $uriPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
    if ($uriPath !== '/' && !str_contains($uriPath, '..')) {
        $publicFile = __DIR__ . '/public' . $uriPath;
        if (is_file($publicFile)) {
            $ext = strtolower(pathinfo($publicFile, PATHINFO_EXTENSION));
            $mimes = [
                'css'  => 'text/css; charset=utf-8',
                'js'   => 'application/javascript; charset=utf-8',
                'json' => 'application/json; charset=utf-8',
                'svg'  => 'image/svg+xml',
                'png'  => 'image/png',
                'jpg'  => 'image/jpeg',
                'jpeg' => 'image/jpeg',
                'webp' => 'image/webp',
                'ico'  => 'image/x-icon',
                'woff2' => 'font/woff2',
                'woff'  => 'font/woff',
                'ttf'   => 'font/ttf',
                'eot'   => 'application/vnd.ms-fontobject',
                'otf'   => 'font/otf',
            ];
            if (isset($mimes[$ext])) {
                header('Content-Type: ' . $mimes[$ext]);
            }
            readfile($publicFile);
            exit;
        }
    }
}

require __DIR__ . '/public/index.php';
