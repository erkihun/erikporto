<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Jobs\AnnounceOnTelegram;
use App\Services\Telegram\TelegramChannel;

/**
 * Queues the announcement as soon as a record goes live.
 * Scheduled notes are picked up by `portfolio:announce` instead.
 *
 * Using classes must implement AnnouncesOnTelegram and define TELEGRAM_TOGGLE.
 */
trait QueuesTelegramAnnouncement
{
    public static function bootQueuesTelegramAnnouncement(): void
    {
        static::saved(static function (self $record): void {
            if (
                config('services.telegram.'.static::TELEGRAM_TOGGLE)
                && $record->isLive()
                && ! $record->wasAnnounced()
                && app(TelegramChannel::class)->isConfigured()
            ) {
                AnnounceOnTelegram::dispatch($record);
            }
        });
    }
}
