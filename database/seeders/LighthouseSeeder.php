<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Note;
use App\Models\NowUpdate;
use Illuminate\Database\Seeder;

/**
 * Representative content for Lighthouse CI (scripts/perf-server.mjs): the demo
 * content plus one note with headings, a list and a code block, and one /now
 * update. Never runs in production.
 */
final class LighthouseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(DemoContentSeeder::class);

        Note::query()->updateOrCreate(['slug' => 'storing-ethiopian-dates'], [
            'title' => 'Storing Ethiopian dates in Laravel',
            'excerpt' => 'Why the database keeps Gregorian dates and the Ethiopian calendar is rendered at the edge.',
            'tags' => ['laravel', 'calendar'],
            'published_at' => now()->subDays(2),
            'body' => <<<'MD'
                ## Why store Gregorian

                The database stays simple and sortable. Every report, index and comparison works on one calendar, and
                the conversion happens only where people read the date.

                ## Rendering E.C. at the edge

                Convert in the view layer with a Julian Day Number helper, so the same value prints as `2026-10-13`
                or as ጥቅምት 3, 2019 ዓ.ም. depending on the reader.

                ### Edge cases

                - Pagume has five or six days.
                - Leap years follow the Julian rule.

                ```php
                $date->format('Y-m-d'); // what the database stores
                ```
                MD,
        ]);

        NowUpdate::query()->updateOrCreate(['id' => 1], [
            'published_at' => now()->subDays(3),
            'body' => <<<'MD'
                ## Work

                Turning this portfolio into a measured, fast, bilingual site.

                ## Learning

                - Variable font subsetting
                - The Ethiopian calendar in JavaScript
                MD,
        ]);
    }
}
