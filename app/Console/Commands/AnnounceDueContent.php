<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Jobs\AnnounceOnTelegram;
use App\Models\Note;
use App\Models\Project;
use App\Services\Telegram\TelegramChannel;
use Illuminate\Console\Command;
use Symfony\Component\Console\Attribute\AsCommand;

/**
 * Safety net for scheduled notes (and anything a failed dispatch missed).
 * Runs every five minutes from routes/console.php.
 */
#[AsCommand(name: 'portfolio:announce', description: 'Queue Telegram announcements for content that is live but not yet posted')]
final class AnnounceDueContent extends Command
{
    public function handle(TelegramChannel $channel): int
    {
        if (! $channel->isConfigured()) {
            $this->components->info('Telegram is not configured; nothing to do.');

            return self::SUCCESS;
        }

        $count = 0;

        if (config('services.telegram.announce_notes')) {
            Note::query()->published()->whereNull('telegram_posted_at')->oldest('published_at')
                ->each(function (Note $note) use (&$count): void {
                    AnnounceOnTelegram::dispatch($note);
                    $count++;
                });
        }

        if (config('services.telegram.announce_projects')) {
            Project::query()->published()->whereNull('telegram_posted_at')->ordered()
                ->each(function (Project $project) use (&$count): void {
                    AnnounceOnTelegram::dispatch($project);
                    $count++;
                });
        }

        $this->components->info("Queued {$count} announcement(s).");

        return self::SUCCESS;
    }
}
