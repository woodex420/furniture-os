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
                // Null means the original first-slide workspace image.
                $table->string('hero_image', 20)->nullable();
            });
        }
    }

    public function down(): void
    {
        foreach (['storefront_home_versions', 'storefront_home_content'] as $name) {
            Schema::table($name, function (Blueprint $table): void {
                $table->dropColumn('hero_image');
            });
        }
    }
};
