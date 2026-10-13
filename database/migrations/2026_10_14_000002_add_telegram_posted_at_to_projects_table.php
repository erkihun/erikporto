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
        Schema::table('projects', function (Blueprint $table): void {
            $table->timestamp('telegram_posted_at')->nullable()->after('sort_order');
        });

        // Projects that are already live must not be announced in a burst
        // the first time the Telegram toggle is switched on.
        DB::table('projects')->where('is_published', true)->update(['telegram_posted_at' => now()]);
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table): void {
            $table->dropColumn('telegram_posted_at');
        });
    }
};
