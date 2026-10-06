<?php
namespace App\Core;

final class Router
{
    private array $routes = [];

    public function get(string $pattern, callable|array $handler): void { $this->add('GET', $pattern, $handler); }
    public function post(string $pattern, callable|array $handler): void { $this->add('POST', $pattern, $handler); }

    private function add(string $method, string $pattern, callable|array $handler): void
    {
        $pattern = rtrim($pattern, '/') ?: '/';
        $regex = preg_replace('#\{([a-zA-Z_][a-zA-Z0-9_]*)\}#', '(?P<$1>[^/]+)', $pattern);
        $this->routes[] = [$method, '#^' . $regex . '/?$#', $handler];
    }

    public function dispatch(string $method, string $uri): void
    {
        $path = parse_url($uri, PHP_URL_PATH) ?: '/';
        foreach ($this->routes as [$routeMethod, $regex, $handler]) {
            if ($routeMethod !== $method || !preg_match($regex, $path, $matches)) continue;
            $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
            if (is_array($handler)) {
                [$class, $action] = $handler;
                (new $class())->{$action}(...array_values($params));
            } else {
                $handler(...array_values($params));
            }
            return;
        }
        http_response_code(404);
        View::render('errors/404');
    }
}
