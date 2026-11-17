<?php

declare(strict_types=1);

// Built-in-server router for disposable CI smoke tests only; not Apache or Hostinger.
if (getenv('CI') !== 'true' || getenv('GITHUB_ACTIONS') !== 'true') {
    http_response_code(403);
    exit;
}

$root = realpath(getcwd().'/public_html');
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$file = is_string($path) ? realpath($root.$path) : false;
if ($root && $file && str_starts_with($file, $root.DIRECTORY_SEPARATOR) && is_file($file)) {
    return false;
}

require $root.'/index.php';
