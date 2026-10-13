<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('skill_groups', function (Blueprint $table): void {
            $table->id();
            $table->string('discipline', 20);
            $table->string('label', 60);
            $table->json('items');                       // list<string>
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['discipline', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('skill_groups');
    }
};
