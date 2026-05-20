<?php

declare(strict_types=1);

function match_route(string $method, string $path, array $routes): ?array
{
    foreach ($routes as $route) {
        if (($route['method'] ?? '') !== $method) {
            continue;
        }

        $params = match_route_path((string) ($route['path'] ?? ''), $path);

        if ($params === null) {
            continue;
        }

        $route['params'] = $params;

        return $route;
    }

    return null;
}

function match_route_path(string $routePath, string $requestPath): ?array
{
    $routeParts = split_route_path($routePath);
    $requestParts = split_route_path($requestPath);

    if (count($routeParts) !== count($requestParts)) {
        return null;
    }

    $params = [];

    foreach ($routeParts as $index => $routePart) {
        $requestPart = $requestParts[$index];

        if (preg_match('/^\{([a-zA-Z_][a-zA-Z0-9_]*)\}$/', $routePart, $matches) === 1) {
            $params[$matches[1]] = rawurldecode($requestPart);
            continue;
        }

        if ($routePart !== $requestPart) {
            return null;
        }
    }

    return $params;
}

function split_route_path(string $path): array
{
    $normalized = '/' . trim($path, '/');

    if ($normalized === '/') {
        return [];
    }

    return explode('/', trim($normalized, '/'));
}

