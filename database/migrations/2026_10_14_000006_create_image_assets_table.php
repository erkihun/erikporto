<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * One row per uploaded image (covers, gallery, portrait, Markdown attachments):
 * its intrinsic size, known as soon as it is saved, and the WebP variants that
 * ProcessUploadedImage writes afterwards.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('image_assets', function (Blueprint $table): void {
            $table->id();
            $table->string('disk', 32);
            $table->string('path')->unique();
            $table->unsignedInteger('width');
            $table->unsignedInteger('height');
            $table->json('variants')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('image_assets');
    }
};
