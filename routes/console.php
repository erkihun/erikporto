<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Schedule;

// Posts scheduled notes (and anything missed) to Telegram once they go live.
Schedule::command('portfolio:announce')->everyFiveMinutes()->withoutOverlapping();
