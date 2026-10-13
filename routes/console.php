<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Schedule;

// Posts scheduled notes (and anything missed) to Telegram once they go live.
Schedule::command('portfolio:announce')->everyFiveMinutes()->withoutOverlapping();

// Processes uploads that have no WebP variants yet and deletes images nothing uses any more.
Schedule::command('portfolio:images')->dailyAt('03:30')->withoutOverlapping();
