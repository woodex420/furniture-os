<?php

declare(strict_types=1);

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// This file belongs in the staging public_html directory. The private Laravel
// application must be the sibling directory, never inside public_html.
$application = dirname(__DIR__).'/furniture-app';

if (file_exists($maintenance = $application.'/storage/framework/maintenance.php')) {
    require $maintenance;
}

require $application.'/vendor/autoload.php';

/** @var Application $app */
$app = require $application.'/bootstrap/app.php';
// Vite and other public_path() lookups must resolve to this actual web root.
$app->usePublicPath(__DIR__);
$app->handleRequest(Request::capture());
