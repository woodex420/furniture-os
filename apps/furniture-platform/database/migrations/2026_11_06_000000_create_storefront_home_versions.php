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
        Schema::create('storefront_home_versions', function (Blueprint $table): void {
            $table->id();
            $table->unsignedInteger('revision')->unique();
            $table->string('hero_title', 120)->nullable();
            $table->string('hero_subtitle', 240)->nullable();
            $table->foreignId('actor_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->string('change_type', 12);
            $table->unsignedInteger('restored_from_revision')->nullable();
            $table->timestamp('created_at');
        });

        // Preserve the currently published state, including null fallback values and
        // its existing revision. Earlier audit rows do not contain historical text.
        $home = DB::table('storefront_home_content')->where('id', 1)->first();
        if ($home !== null) {
            DB::table('storefront_home_versions')->insert([
                'revision' => $home->revision,
                'hero_title' => $home->hero_title,
                'hero_subtitle' => $home->hero_subtitle,
                'actor_id' => null,
                'change_type' => 'baseline',
                'restored_from_revision' => null,
                'created_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('storefront_home_versions');
    }
};
