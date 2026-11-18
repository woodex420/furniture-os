<?php

declare(strict_types=1);

use App\Http\Controllers\Workspace\BuilderHtmlProofController;
use App\Support\Provisioning\ProvisionFirstOwner;
use App\Support\Website\BuilderDocumentRevisions;
use Illuminate\Contracts\Console\Kernel;

// CI-only fixed synthetic SQLite database. Never included in a staging package.
if (getenv('CI') !== 'true' || getenv('GITHUB_ACTIONS') !== 'true'
    || getenv('APP_ENV') !== 'local' || getenv('DB_CONNECTION') !== 'sqlite'
    || getenv('DB_DATABASE') !== '/tmp/furniture-smoke.sqlite' || getenv('DB_URL')
    || getenv('FURNITURE_BUILDER_HTML_PROOF_ENABLED') !== 'true') {
    throw new RuntimeException('Disposable CI proof database required.');
}

require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
$owner = $app->make(ProvisionFirstOwner::class)->provision('Synthetic Owner', 'owner@example.test', 'synthetic-password-for-ci');
$app->make(BuilderDocumentRevisions::class)->create((int) $owner->id, BuilderHtmlProofController::DOCUMENT_ID, [
    'schema_version' => 1,
    'sections' => [[
        'id' => BuilderHtmlProofController::SECTION_ID,
        'type' => 'article_text',
        'props' => ['summary' => 'Initial synthetic summary', 'body' => 'Initial synthetic article.'],
    ]],
]);
