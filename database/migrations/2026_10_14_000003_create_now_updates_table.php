<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Each row is one /now update; the newest published row is the current one.
        Schema::create('now_updates', function (Blueprint $table): void {
            $table->id();
            $table->longText('body');                    // Markdown source, edited in admin
            $table->longText('body_html');               // Sanitised render, rebuilt on every save
            $table->timestamp('published_at')->nullable(); // null = draft, future = scheduled
            $table->timestamps();

            $table->index('published_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('now_updates');
    }
};
