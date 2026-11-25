<?php

declare(strict_types=1);

namespace App\Core;

class Router
{
    private array $routes = [];

    public function get(string $path, array $handler): void
    {
        $this->addRoute('GET', $path, $handler);
    }

    public function post(string $path, array $handler): void
    {
        $this->addRoute('POST', $path, $handler);
    }

    private function addRoute(string $method, string $path, array $handler): void
    {
        $normalized = '/' . trim($path, '/');
        if ($normalized !== '/') {
            $normalized = rtrim($normalized, '/');
        }
        $this->routes[$method][$normalized] = $handler;
    }

    public function dispatch(string $method, string $uri): void
    {
        $path = parse_url($uri, PHP_URL_PATH) ?: '/';

        // Strip subfolder prefix if accessed via /Tiket_Land/public or /Tiket_Land
        $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
        if ($scriptDir !== '/' && $scriptDir !== '.' && str_starts_with($path, $scriptDir)) {
            $path = substr($path, strlen($scriptDir)) ?: '/';
        }

        $normalized = '/' . trim($path, '/');
        if ($normalized !== '/') {
            $normalized = rtrim($normalized, '/');
        }

        $method = strtoupper($method);
        $handler = $this->routes[$method][$normalized] ?? null;

        if ($handler !== null) {
            [$controllerClass, $action] = $handler;
            $controller = new $controllerClass();
            $controller->$action();
            return;
        }

        // Check dynamic pattern routes such as /event/{id}
        foreach ($this->routes[$method] ?? [] as $routePattern => $routeHandler) {
            if (!str_contains($routePattern, '{')) {
                continue;
            }
            $regex = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '(?P<$1>[^/]+)', $routePattern);
            $regex = '#^' . $regex . '$#';
            if (preg_match($regex, $normalized, $matches)) {
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
                [$controllerClass, $action] = $routeHandler;
                $controller = new $controllerClass();
                $controller->$action(...array_values($params));
                return;
            }
        }

        http_response_code(404);
        echo '404 - Halaman tidak ditemukan';
    }
}
