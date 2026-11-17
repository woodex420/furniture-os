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
        // This is a provisional, unrouted candidate. MySQL locking and upgrade safety are unverified.
        Schema::create('customer_write_guards', function (Blueprint $table): void {
            $table->unsignedTinyInteger('id')->primary();
        });
        DB::table('customer_write_guards')->insert(['id' => 1]);

        Schema::create('contact_creation_requests', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('actor_id')->constrained('users')->restrictOnDelete();
            $table->uuid('request_key');
            $table->char('payload_hash', 64);
            $table->foreignId('contact_id')->constrained('contacts')->restrictOnDelete();
            $table->timestamps();
            $table->unique(['actor_id', 'request_key']);
        });

        Schema::create('customer_audit_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('actor_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('contact_id')->constrained('contacts')->restrictOnDelete();
            $table->string('event', 50);
            $table->timestamp('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_audit_events');
        Schema::dropIfExists('contact_creation_requests');
        Schema::dropIfExists('customer_write_guards');
    }
};
