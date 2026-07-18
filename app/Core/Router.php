<?php

declare(strict_types=1);

namespace App\Core;

final class Router
{
    /** @var array<int, array{method:string, pattern:string, regex:string, handler:array, opts:array}> */
    private array $routes = [];

    public function get(string $pattern, array $handler, array $opts = []): void
    {
        $this->add('GET', $pattern, $handler, $opts);
    }

    public function post(string $pattern, array $handler, array $opts = []): void
    {
        $this->add('POST', $pattern, $handler, $opts);
    }

    public function put(string $pattern, array $handler, array $opts = []): void
    {
        $this->add('PUT', $pattern, $handler, $opts);
    }

    public function delete(string $pattern, array $handler, array $opts = []): void
    {
        $this->add('DELETE', $pattern, $handler, $opts);
    }

    private function add(string $method, string $pattern, array $handler, array $opts): void
    {
        $regex = preg_replace('#\{([a-zA-Z_][a-zA-Z0-9_]*)\}#', '(?P<$1>[^/]+)', $pattern);
        $this->routes[] = [
            'method' => $method,
            'pattern' => $pattern,
            'regex' => '#^' . $regex . '$#',
            'handler' => $handler,
            'opts' => $opts,
        ];
    }

    /**
     * @return array{handler:array, params:array, opts:array}|null
     */
    public function match(string $method, string $path): ?array
    {
        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }
            if (preg_match($route['regex'], $path, $matches)) {
                $params = array_filter($matches, fn ($k) => is_string($k), ARRAY_FILTER_USE_KEY);
                return ['handler' => $route['handler'], 'params' => $params, 'opts' => $route['opts']];
            }
        }
        return null;
    }
}
