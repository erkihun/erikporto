<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table): void {
            $table->id();
            $table->string('slug', 120)->unique();
            $table->string('title', 120);
            $table->string('subtitle', 200)->nullable();
            $table->string('category', 20);
            $table->string('summary', 300);
            $table->text('problem');
            $table->text('role');
            $table->text('solution');
            $table->text('outcome');
            $table->json('metrics')->nullable();         // list<{value,label}>
            $table->json('stack');                       // list<string>
            $table->json('tools')->nullable();           // list<string>
            $table->string('repository_url')->nullable();
            $table->string('live_url')->nullable();
            $table->string('cover_path')->nullable();
            $table->json('gallery')->nullable();         // list<string> storage paths
            $table->string('mock', 20)->nullable();      // illustrated fallback when no cover
            $table->unsignedSmallInteger('year')->nullable();
            $table->boolean('is_published')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['is_published', 'sort_order']);
            $table->index('category');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
