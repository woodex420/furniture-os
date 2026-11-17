<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['storefront_home_content', 'storefront_home_versions'] as $name) {
            Schema::table($name, function (Blueprint $table): void {
                // Null retains the bundled background. History also protects referenced files.
                $table->uuid('hero_media_id')->nullable();
                $table->foreign('hero_media_id')->references('id')->on('furniture_media')->restrictOnDelete();
            });
        }
    }

    public function down(): void
    {
        foreach (['storefront_home_versions', 'storefront_home_content'] as $name) {
            Schema::table($name, function (Blueprint $table): void {
                $table->dropForeign(['hero_media_id']);
                $table->dropColumn('hero_media_id');
            });
        }
    }
};
