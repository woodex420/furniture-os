<?php

declare(strict_types=1);

use App\Support\Provisioning\ProvisionFirstOwner;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;

// Disposable GitHub service only. This file is never included in the package.
if (getenv('CI') !== 'true' || getenv('GITHUB_ACTIONS') !== 'true'
    || getenv('DB_HOST') !== '127.0.0.1' || getenv('DB_DATABASE') !== 'furniture_imported_test') {
    throw new RuntimeException('Synthetic CI database required.');
}

require getcwd().'/vendor/autoload.php';
$app = require getcwd().'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
$app->make(ProvisionFirstOwner::class)->provision('Synthetic Owner', 'owner@example.test', 'synthetic-password-for-ci');
// Synthetic lead statuses exercise the read-only Livewire filter; no production data.
DB::table('leads')->insert([
    ['title' => 'Synthetic new enquiry', 'status' => 'new', 'created_at' => now(), 'updated_at' => now()],
    ['title' => 'Synthetic qualified enquiry', 'status' => 'qualified', 'created_at' => now(), 'updated_at' => now()],
]);
