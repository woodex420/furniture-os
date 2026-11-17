<?php

declare(strict_types=1);

// Disposable GitHub service only. This file is never included in the package.
if (getenv('CI') !== 'true' || getenv('GITHUB_ACTIONS') !== 'true'
    || getenv('DB_HOST') !== '127.0.0.1' || getenv('DB_DATABASE') !== 'furniture_imported_test') {
    throw new RuntimeException('Synthetic CI database required.');
}

require getcwd().'/vendor/autoload.php';
$app = require getcwd().'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$app->make(App\Support\Provisioning\ProvisionFirstOwner::class)->provision('Synthetic Owner', 'owner@example.test', 'synthetic-password-for-ci');
