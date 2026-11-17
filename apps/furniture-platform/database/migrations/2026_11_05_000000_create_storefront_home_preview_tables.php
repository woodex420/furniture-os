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
        // Initial integration is deliberately limited to the first homepage slide.
        Schema::create('storefront_home_content', function (Blueprint $table): void {
            $table->unsignedTinyInteger('id')->primary();
            $table->string('hero_title', 120)->nullable();
            $table->string('hero_subtitle', 240)->nullable();
            $table->unsignedInteger('revision')->default(0);
            $table->timestamps();
        });
        DB::table('storefront_home_content')->insert(['id' => 1, 'revision' => 0, 'created_at' => now(), 'updated_at' => now()]);
        Schema::create('storefront_home_changes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('actor_id')->constrained('users')->restrictOnDelete();
            $table->unsignedInteger('revision');
            $table->timestamp('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('storefront_home_changes');
        Schema::dropIfExists('storefront_home_content');
    }
};
