<?php

declare(strict_types=1);

/**
 * Router para o servidor embutido do PHP (php -S), usado apenas em
 * desenvolvimento. Sem isto, o servidor embutido tenta servir como ficheiro
 * estático qualquer caminho com extensão reconhecida (ex.: dossie.zip) e
 * devolve 404 em vez de invocar o front controller — o Apache com
 * .htaccess (produção) não tem este problema, pois reescreve sempre para
 * index.php.
 *
 * Uso: php -S localhost:8000 -t public bin/dev_router.php
 */

$caminho = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '/';
$ficheiro = __DIR__ . '/../public' . $caminho;

if ($caminho !== '/' && is_file($ficheiro)) {
    return false; // deixa o servidor embutido servir o ficheiro estático (css/js/img) diretamente
}

require __DIR__ . '/../public/index.php';
