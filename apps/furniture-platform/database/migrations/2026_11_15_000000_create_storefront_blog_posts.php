<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('storefront_blog_posts', function (Blueprint $table): void {
            $table->string('slug', 80)->primary();
            $table->string('title', 160);
            $table->string('summary', 300);
            $table->text('body');
            $table->boolean('published')->default(false)->index();
            $table->unsignedInteger('revision');
            $table->timestamps();
        });
        Schema::create('storefront_blog_post_versions', function (Blueprint $table): void {
            $table->id();
            $table->string('slug', 80);
            $table->unsignedInteger('revision');
            $table->string('title', 160);
            $table->string('summary', 300);
            $table->text('body');
            $table->boolean('published');
            $table->foreignId('actor_id')->constrained('users')->restrictOnDelete();
            $table->string('change_type', 12);
            $table->unsignedInteger('restored_from_revision')->nullable();
            $table->timestamp('created_at');
            $table->unique(['slug', 'revision']);
            $table->foreign('slug')->references('slug')->on('storefront_blog_posts')->restrictOnDelete();
        });
        // Do not import sample articles, Interior content, or browser-local demo data.
    }

    public function down(): void
    {
        Schema::dropIfExists('storefront_blog_post_versions');
        Schema::dropIfExists('storefront_blog_posts');
    }
};
