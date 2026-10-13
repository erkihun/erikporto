<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Singleton: the application only ever reads the first row.
        Schema::create('profiles', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 120);
            $table->string('headline', 160);
            $table->string('lede', 400);
            $table->json('bio');                         // list<string> paragraphs
            $table->string('about_statement', 240)->nullable();
            $table->string('location', 120)->nullable();
            $table->string('email', 190)->nullable();
            $table->boolean('is_available')->default(true);
            $table->string('avatar_path')->nullable();
            $table->string('cv_path')->nullable();
            $table->json('social_links')->nullable();    // list<{label,url,handle}>
            $table->json('facts')->nullable();           // list<{value,label}>
            $table->string('meta_description', 300)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('profiles');
    }
};
