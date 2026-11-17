<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('storefront_city_pages', function (Blueprint $table): void {
            $table->string('slug', 80)->primary();
            $table->string('city_name', 100);
            $table->string('title', 120);
            $table->string('summary', 240);
            $table->text('body');
            $table->boolean('published')->default(false)->index();
            $table->unsignedInteger('revision');
            $table->timestamps();
        });
        Schema::create('storefront_city_page_versions', function (Blueprint $table): void {
            $table->id();
            $table->string('slug', 80);
            $table->unsignedInteger('revision');
            $table->string('city_name', 100);
            $table->string('title', 120);
            $table->string('summary', 240);
            $table->text('body');
            $table->boolean('published');
            $table->foreignId('actor_id')->constrained('users')->restrictOnDelete();
            $table->string('change_type', 12);
            $table->unsignedInteger('restored_from_revision')->nullable();
            $table->timestamp('created_at');
            $table->unique(['slug', 'revision']);
            $table->foreign('slug')->references('slug')->on('storefront_city_pages')->restrictOnDelete();
        });
        // Intentionally seed no locations or claims.
    }

    public function down(): void
    {
        Schema::dropIfExists('storefront_city_page_versions');
        Schema::dropIfExists('storefront_city_pages');
    }
};
