<?php

declare(strict_types=1);

namespace App\Core;

final class View
{
    private static string $basePath = '';

    public static function init(string $basePath): void
    {
        self::$basePath = rtrim($basePath, '/');
    }

    public static function render(string $template, array $data = [], ?string $layout = 'app'): string
    {
        $content = self::renderTemplate($template, $data);

        if ($layout === null) {
            return $content;
        }

        return self::renderTemplate('layouts/' . $layout, array_merge($data, ['content' => $content]));
    }

    private static function renderTemplate(string $template, array $data): string
    {
        $file = self::$basePath . '/' . $template . '.php';
        if (!is_file($file)) {
            throw new \RuntimeException("View não encontrada: {$template}");
        }

        extract($data, EXTR_SKIP);
        ob_start();
        require $file;
        return (string) ob_get_clean();
    }

    public static function partial(string $template, array $data = []): string
    {
        return self::renderTemplate('partials/' . $template, $data);
    }
}
