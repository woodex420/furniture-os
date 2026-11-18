<?php

declare(strict_types=1);

use App\Models\User;
use App\Support\Customers\CreateLeadCandidate;
use App\Support\Customers\EditLeadCandidate;
use App\Support\Website\BuilderDocumentRevisions;
use App\Support\Website\BuilderDocumentV1;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

// CI-only, synthetic fixture. Never point at a staging or production database.
if (getenv('CI') !== 'true' || getenv('GITHUB_ACTIONS') !== 'true'
    || getenv('APP_ENV') !== 'testing' || getenv('DB_CONNECTION') !== 'mysql'
    || getenv('DB_HOST') !== '127.0.0.1' || getenv('DB_PORT') !== '3306'
    || getenv('DB_DATABASE') !== 'furniture_imported_test' || getenv('DB_URL')) {
    throw new RuntimeException('This check is restricted to the disposable MySQL CI fixture.');
}

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

function verify(bool $condition, string $message): void
{
    if (! $condition) {
        throw new RuntimeException($message);
    }
}

$imported = DB::connection();
$sourceName = 'furniture_source_test';
$importName = 'furniture_imported_test';
verify($imported->getDatabaseName() === $importName, 'Unexpected database connection.');

// Compare physical definitions, including keys and referential rules; ignore the
// schema name and dynamic AUTO_INCREMENT counter, which naturally differ.
$queries = [
    'tables' => 'SELECT TABLE_NAME, TABLE_TYPE, ENGINE, TABLE_COLLATION FROM information_schema.TABLES WHERE TABLE_SCHEMA = ? ORDER BY TABLE_NAME',
    'columns' => 'SELECT TABLE_NAME, COLUMN_NAME, ORDINAL_POSITION, COLUMN_DEFAULT, IS_NULLABLE, COLUMN_TYPE, CHARACTER_SET_NAME, COLLATION_NAME, EXTRA, GENERATION_EXPRESSION FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = ? ORDER BY TABLE_NAME, ORDINAL_POSITION',
    'indexes' => 'SELECT TABLE_NAME, INDEX_NAME, NON_UNIQUE, SEQ_IN_INDEX, COLUMN_NAME, SUB_PART, INDEX_TYPE, COLLATION FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = ? ORDER BY TABLE_NAME, INDEX_NAME, SEQ_IN_INDEX',
    'foreign keys' => 'SELECT TABLE_NAME, CONSTRAINT_NAME, COLUMN_NAME, ORDINAL_POSITION, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE CONSTRAINT_SCHEMA = ? AND REFERENCED_TABLE_NAME IS NOT NULL ORDER BY TABLE_NAME, CONSTRAINT_NAME, ORDINAL_POSITION',
    'referential rules' => 'SELECT TABLE_NAME, CONSTRAINT_NAME, UPDATE_RULE, DELETE_RULE, MATCH_OPTION FROM information_schema.REFERENTIAL_CONSTRAINTS WHERE CONSTRAINT_SCHEMA = ? ORDER BY TABLE_NAME, CONSTRAINT_NAME',
];
foreach ($queries as $label => $query) {
    $source = $imported->select($query, [$sourceName]);
    $copy = $imported->select($query, [$importName]);
    verify(count($source) > 0 && $source == $copy, "Imported {$label} differ from the migrated source.");
}

$baseline = ['migrations', 'customer_write_guards', 'storefront_home_content', 'storefront_home_versions', 'storefront_service_copy', 'storefront_service_copy_versions'];
foreach ($baseline as $table) {
    $source = $imported->select("SELECT * FROM `{$sourceName}`.`{$table}` ORDER BY 1");
    $copy = $imported->select("SELECT * FROM `{$importName}`.`{$table}` ORDER BY 1");
    verify(count($source) > 0 && $source == $copy, "Missing or mismatched installation defaults: {$table}.");
}
// The SQL must contain no accounts or business records. Tables are discovered
// from the imported database, not taken from an untrusted external database.
$tables = $imported->select('SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA = ? ORDER BY TABLE_NAME', [$importName]);
foreach ($tables as $table) {
    $name = $table->TABLE_NAME;
    if (! in_array($name, $baseline, true)) {
        verify($imported->table($name)->count() === 0, "Unexpected data in {$name}.");
    }
}
$files = glob(__DIR__.'/../database/migrations/*.php');
verify(count($files) === $imported->table('migrations')->count(), 'Migration bookkeeping does not match current migrations.');
verify($imported->table('customer_write_guards')->where('id', 1)->exists(), 'Missing write guard.');

// Exercise the imported schema with disposable owner/lead writes, then roll back.
config(['furniture.lead_editing_enabled' => true]);
$imported->beginTransaction();
try {
    $owner = new User;
    $owner->forceFill(['name' => 'Synthetic owner', 'email' => 'owner@example.test', 'password' => 'synthetic-only-password', 'is_owner' => true])->save();
    Auth::setUser($owner);
    $lead = app(CreateLeadCandidate::class)->create([
        'title' => 'Synthetic lead', 'source' => 'CI', 'contact_id' => null,
        'request_key' => 'fd9c381e-4298-4037-8cae-8aa54be328da',
    ]);
    $edited = app(EditLeadCandidate::class)->update((int) $lead->id, ['title' => 'Synthetic updated', 'source' => 'CI', 'edit_revision' => 0]);
    verify($edited->title === 'Synthetic updated' && (int) $edited->edit_revision === 1, 'Imported lead write failed.');
    verify($imported->table('lead_audit_events')->where('lead_id', $lead->id)->count() === 2, 'Expected lead audit events missing.');

    $documentId = 'c6ae6052-11ad-4d16-8a18-56541c735d8c';
    $document = ['schema_version' => 1, 'sections' => [['id' => 'e38c052c-f2e0-43ca-85d0-a5760caa7d03', 'type' => 'article_text', 'props' => ['summary' => 'خلاصہ', 'body' => 'Synthetic <text>']]]];
    $builder = app(BuilderDocumentRevisions::class);
    $builder->create((int) $owner->id, $documentId, $document);
    $updated = $document;
    $updated['sections'][0]['props']['body'] = 'Updated synthetic text';
    $builder->edit((int) $owner->id, $documentId, 1, $updated);
    $builder->restore((int) $owner->id, $documentId, 2, 1);
    $row = $imported->table('furniture_builder_documents')->where('id', $documentId)->first();
    verify((int) $row->revision === 3 && app(BuilderDocumentV1::class)->decode($row->document) === $document, 'Builder document restore failed.');
    verify($imported->table('furniture_builder_document_versions')->where('document_id', $documentId)->count() === 3, 'Builder revision history missing.');
} finally {
    $imported->rollBack();
    Auth::forgetGuards();
}
verify($imported->table('users')->count() === 0 && $imported->table('leads')->count() === 0 && $imported->table('furniture_builder_documents')->count() === 0 && $imported->table('furniture_builder_document_versions')->count() === 0, 'Synthetic write rollback failed.');
echo "PASS: schema, keys, defaults, lead writes and builder document revisions on disposable imported MySQL/MariaDB.\n";
