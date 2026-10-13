<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Contracts\AnnouncesOnTelegram;
use App\Services\Telegram\TelegramChannel;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;

/**
 * Posts a newly live note or project to the Telegram channel exactly once.
 *
 * Idempotency has three layers: ShouldBeUnique stops duplicate queueing,
 * a cache lock stops two workers racing, and `telegram_posted_at` is checked
 * again inside the lock before sending.
 */
final class AnnounceOnTelegram implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $tries = 5;

    /** @var list<int> */
    public array $backoff = [30, 120, 600, 1800];

    public int $uniqueFor = 3600;

    /** @param  Model&AnnouncesOnTelegram  $record */
    public function __construct(public readonly Model $record)
    {
        $this->afterCommit();
    }

    public function uniqueId(): string
    {
        return $this->record::class.':'.$this->record->getKey();
    }

    public function handle(TelegramChannel $channel): void
    {
        if (! $channel->isConfigured()) {
            return;
        }

        Cache::lock('telegram:'.$this->uniqueId(), 30)->block(10, function () use ($channel): void {
            $record = $this->record->fresh();

            if (! $record instanceof AnnouncesOnTelegram || ! $record->isLive() || $record->wasAnnounced()) {
                return; // deleted, unpublished, or already posted
            }

            $channel->post($record->telegramMessage());
            $record->markAnnounced();
        });
    }
}
