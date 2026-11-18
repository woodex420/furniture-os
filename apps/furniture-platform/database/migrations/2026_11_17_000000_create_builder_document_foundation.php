<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Isolated internal documents: no route, publication, or imported content.
        Schema::create('furniture_builder_documents', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->longText('document');
            $table->unsignedInteger('revision');
            $table->timestamps();
        });
        Schema::create('furniture_builder_document_versions', function (Blueprint $table): void {
            $table->id();
            $table->uuid('document_id');
            $table->unsignedInteger('revision');
            $table->longText('document');
            $table->foreignId('actor_id')->constrained('users')->restrictOnDelete();
            $table->string('change_type', 12);
            $table->unsignedInteger('restored_from_revision')->nullable();
            $table->timestamp('created_at');
            $table->unique(['document_id', 'revision'], 'builder_document_revision_unique');
            $table->foreign('document_id', 'builder_version_document_fk')->references('id')->on('furniture_builder_documents')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('furniture_builder_document_versions');
        Schema::dropIfExists('furniture_builder_documents');
    }
};
