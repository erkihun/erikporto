<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notes', function (Blueprint $table): void {
            $table->id();
            $table->string('slug', 140)->unique();
            $table->string('title', 160);
            $table->string('excerpt', 300);
            $table->longText('body');                    // Markdown source, edited in admin
            $table->longText('body_html');               // Sanitised render, rebuilt on every save
            $table->json('toc')->nullable();             // list<{id,text,level}>
            $table->unsignedSmallInteger('reading_minutes')->default(1);
            $table->json('tags')->nullable();
            $table->string('cover_path')->nullable();
            // Single source of truth for state: null = draft, future = scheduled, past = published.
            $table->timestamp('published_at')->nullable();
            $table->timestamp('telegram_posted_at')->nullable();
            $table->timestamps();

            $table->index('published_at');
            $table->index(['telegram_posted_at', 'published_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notes');
    }
};
