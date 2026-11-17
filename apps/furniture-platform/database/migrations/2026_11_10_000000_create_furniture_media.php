<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('furniture_media', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('title', 120);
            $table->string('mime', 20);
            $table->unsignedInteger('width');
            $table->unsignedInteger('height');
            $table->unsignedInteger('bytes');
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        // Metadata only: files are deliberately not deleted by migration rollback.
        Schema::dropIfExists('furniture_media');
    }
};
