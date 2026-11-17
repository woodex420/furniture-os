<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Experimental tables; target MySQL/MariaDB and restore behavior remain unverified.
        Schema::create('lead_creation_requests', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('actor_id')->constrained('users')->restrictOnDelete();
            $table->uuid('request_key');
            $table->char('payload_hash', 64);
            $table->foreignId('lead_id')->constrained('leads')->restrictOnDelete();
            $table->timestamps();
            $table->unique(['actor_id', 'request_key']);
        });
        Schema::create('lead_audit_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('actor_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('lead_id')->constrained('leads')->restrictOnDelete();
            $table->string('event', 50);
            $table->timestamp('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lead_audit_events');
        Schema::dropIfExists('lead_creation_requests');
    }
};
