<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Schedule;

// Posts scheduled notes (and anything missed) to Telegram once they go live.
Schedule::command('portfolio:announce')->everyFiveMinutes()->withoutOverlapping();

// Backups (config/backup.php): prune old archives, take tonight's, and mail if the newest
// one is missing or too old.
Schedule::command('backup:clean')->dailyAt('01:00')->withoutOverlapping();
Schedule::command('backup:run')->dailyAt('01:30')->withoutOverlapping();
Schedule::command('backup:monitor')->dailyAt('09:00');

// Processes uploads that have no WebP variants yet and deletes images nothing uses any more.
Schedule::command('portfolio:images')->dailyAt('03:30')->withoutOverlapping();
