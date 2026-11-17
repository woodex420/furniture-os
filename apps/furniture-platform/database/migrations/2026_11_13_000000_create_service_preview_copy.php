<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('storefront_service_copy', function (Blueprint $table): void {
            $table->string('slug', 80)->primary();
            $table->string('title', 120)->nullable();
            $table->string('tagline', 240)->nullable();
            $table->unsignedInteger('revision')->default(0);
            $table->timestamps();
        });
        Schema::create('storefront_service_copy_versions', function (Blueprint $table): void {
            $table->id();
            $table->string('slug', 80);
            $table->unsignedInteger('revision');
            $table->string('title', 120)->nullable();
            $table->string('tagline', 240)->nullable();
            $table->foreignId('actor_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->string('change_type', 12);
            $table->unsignedInteger('restored_from_revision')->nullable();
            $table->timestamp('created_at');
            $table->unique(['slug', 'revision']);
            $table->foreign('slug')->references('slug')->on('storefront_service_copy')->restrictOnDelete();
        });
        // IDs only, not copied Interior data or unverified Furniture service claims.
        foreach (['custom-design', 'b2b-office-solutions', 'custom-manufacturing', 'delivery-installation', 'space-planning-design', 'after-sales-support', 'project-management'] as $slug) {
            DB::table('storefront_service_copy')->insert(['slug' => $slug, 'revision' => 0, 'created_at' => now(), 'updated_at' => now()]);
            DB::table('storefront_service_copy_versions')->insert(['slug' => $slug, 'revision' => 0, 'title' => null, 'tagline' => null, 'actor_id' => null, 'change_type' => 'baseline', 'restored_from_revision' => null, 'created_at' => now()]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('storefront_service_copy_versions');
        Schema::dropIfExists('storefront_service_copy');
    }
};
