<?php

declare(strict_types=1);

namespace App\Core;

final class Request
{
    public readonly string $method;
    public readonly string $path;
    private array $query;
    private array $body;
    private array $files;

    public function __construct()
    {
        $this->method = $this->resolveMethod();
        $this->path = $this->resolvePath();
        $this->query = $_GET;
        $this->files = $_FILES;

        if (str_contains($_SERVER['CONTENT_TYPE'] ?? '', 'application/json')) {
            $raw = file_get_contents('php://input');
            $this->body = $raw ? (json_decode($raw, true) ?? []) : [];
        } else {
            $this->body = $_POST;
        }
    }

    private function resolveMethod(): string
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        if ($method === 'POST' && isset($_POST['_method'])) {
            $override = strtoupper((string) $_POST['_method']);
            if (in_array($override, ['PUT', 'PATCH', 'DELETE'], true)) {
                $method = $override;
            }
        }
        return $method;
    }

    private function resolvePath(): string
    {
        // REQUEST_URI reflete sempre o caminho original pedido pelo browser,
        // tanto sob Apache (mod_rewrite reescreve para index.php?url=... mas
        // REQUEST_URI mantém-se) como sob o servidor embutido do PHP (que não
        // lê .htaccess, logo $_GET['url'] nunca seria definido).
        $uri = $_SERVER['REQUEST_URI'] ?? ($_GET['url'] ?? '/');
        $path = parse_url((string) $uri, PHP_URL_PATH) ?? '/';
        $path = '/' . ltrim($path, '/');
        $path = rtrim($path, '/');
        return $path === '' ? '/' : $path;
    }

    public function input(string $key, mixed $default = null): mixed
    {
        return $this->body[$key] ?? $this->query[$key] ?? $default;
    }

    public function all(): array
    {
        return array_merge($this->query, $this->body);
    }

    public function query(string $key, mixed $default = null): mixed
    {
        return $this->query[$key] ?? $default;
    }

    public function file(string $key): ?array
    {
        return $this->files[$key] ?? null;
    }

    public function files(string $key): array
    {
        $raw = $this->files[$key] ?? null;
        if (!$raw) {
            return [];
        }
        if (!is_array($raw['name'])) {
            return [$raw];
        }
        $out = [];
        foreach ($raw['name'] as $i => $name) {
            $out[] = [
                'name' => $name,
                'type' => $raw['type'][$i],
                'tmp_name' => $raw['tmp_name'][$i],
                'error' => $raw['error'][$i],
                'size' => $raw['size'][$i],
            ];
        }
        return $out;
    }

    public function ip(): string
    {
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }

    public function userAgent(): string
    {
        return $_SERVER['HTTP_USER_AGENT'] ?? '';
    }

    public function isAjax(): bool
    {
        return strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest'
            || str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json');
    }

    public function wantsJson(): bool
    {
        return $this->isAjax() || str_starts_with($this->path, '/api/');
    }
}
