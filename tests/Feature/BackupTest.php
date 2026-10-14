<?php

declare(strict_types=1);

use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Storage;
use Spatie\Backup\Notifications\Notifications\BackupHasFailedNotification;
use Spatie\Backup\Notifications\Notifications\BackupWasSuccessfulNotification;

it('backs up the uploads and private files with paths that restore in place', function (): void {
    Storage::fake('backups');
    $marker = storage_path('app/private/backup-test.txt');
    file_put_contents($marker, 'restore me');

    try {
        $this->artisan('backup:run', ['--only-files' => true, '--disable-notifications' => true])->assertSuccessful();
    } finally {
        @unlink($marker);
    }

    $archives = Storage::disk('backups')->allFiles();
    expect($archives)->toHaveCount(1);

    $zip = new ZipArchive;
    $zip->open(Storage::disk('backups')->path($archives[0]));
    $entries = [];
    for ($i = 0; $i < $zip->numFiles; $i++) {
        // Windows writes back slashes; servers write storage/app/...
        $entries[str_replace('\\', '/', (string) $zip->getNameIndex($i))] = $i;
    }
    expect($entries)->toHaveKey('storage/app/private/backup-test.txt')
        ->and($zip->getFromIndex($entries['storage/app/private/backup-test.txt']))->toBe('restore me')
        ->and(array_filter(array_keys($entries), static fn (string $name): bool => str_contains($name, 'livewire-tmp')))->toBe([]);
    $zip->close();
});

it('runs the backups, their cleanup and health check every night', function (): void {
    $commands = collect(app(Schedule::class)->events())->map(static fn (Event $event): string => (string) $event->command);

    foreach (['backup:clean', 'backup:run', 'backup:monitor', 'portfolio:images', 'portfolio:announce'] as $command) {
        expect($commands->contains(static fn (string $line): bool => str_contains($line, $command)))->toBeTrue($command);
    }
});

it('keeps 7 daily and 4 weekly backups and only mails about failures', function (): void {
    expect(config('backup.cleanup.default_strategy.keep_daily_backups_for_days'))->toBe(7)
        ->and(config('backup.cleanup.default_strategy.keep_weekly_backups_for_weeks'))->toBe(4)
        ->and(config('backup.notifications.notifications')[BackupHasFailedNotification::class])->toBe(['mail'])
        ->and(config('backup.notifications.notifications')[BackupWasSuccessfulNotification::class])->toBe([]);
});
