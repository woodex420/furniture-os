<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Null in pre-existing rows means the original title-then-subtitle layout.
        Schema::table('storefront_home_content', function (Blueprint $table): void {
            $table->string('hero_order', 20)->nullable();
        });
        Schema::table('storefront_home_versions', function (Blueprint $table): void {
            $table->string('hero_order', 20)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('storefront_home_versions', function (Blueprint $table): void {
            $table->dropColumn('hero_order');
        });
        Schema::table('storefront_home_content', function (Blueprint $table): void {
            $table->dropColumn('hero_order');
        });
    }
};
