<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            // Ordinary accounts are denied workspace access until explicitly authorized.
            $table->boolean('is_owner')->default(false);
        });

        Schema::create('owner_initializations', function (Blueprint $table): void {
            // A fixed primary key makes competing initializations mutually exclusive.
            $table->unsignedTinyInteger('id')->primary();
            $table->timestamp('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('owner_initializations');
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn('is_owner');
        });
    }
};
