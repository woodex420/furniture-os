<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Provisional read foundation, not an approved import or production CRM schema.
        Schema::create('contacts', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('phone', 50)->nullable();
            $table->timestamps();
        });

        Schema::create('leads', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('contact_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->string('source', 100)->nullable();
            $table->string('status', 50)->default('new');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leads');
        Schema::dropIfExists('contacts');
    }
};
