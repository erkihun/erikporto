<?php

declare(strict_types=1);

use Spatie\Backup\Notifications\Notifiable;
use Spatie\Backup\Notifications\Notifications\BackupHasFailedNotification;
use Spatie\Backup\Notifications\Notifications\BackupWasSuccessfulNotification;
use Spatie\Backup\Notifications\Notifications\CleanupHasFailedNotification;
use Spatie\Backup\Notifications\Notifications\CleanupWasSuccessfulNotification;
use Spatie\Backup\Notifications\Notifications\HealthyBackupWasFoundNotification;
use Spatie\Backup\Notifications\Notifications\UnhealthyBackupWasFoundNotification;
use Spatie\Backup\Tasks\Cleanup\Strategies\DefaultStrategy;
use Spatie\Backup\Tasks\Monitor\HealthChecks\MaximumAgeInDays;
use Spatie\Backup\Tasks\Monitor\HealthChecks\MaximumStorageInMegabytes;

/*
 * Nightly backups (spatie/laravel-backup, scheduled in routes/console.php): the database
 * plus everything people uploaded. Code comes from git and is not backed up; neither is
 * .env, so keep APP_KEY in a password manager: it decrypts the admins' MFA secrets.
 * Restore steps: docs/production.md.
 */
return [

    'backup' => [
        'name' => env('APP_NAME', 'portfolio'),

        'source' => [
            'files' => [
                'include' => [
                    storage_path('app/public'),  // images and their WebP variants
                    storage_path('app/private'), // the CV
                ],
                'exclude' => [
                    storage_path('app/private/livewire-tmp'), // unfinished admin uploads
                ],
                'follow_links' => false,
                'ignore_unreadable_directories' => false,
                // Paths inside the archive start at storage/, as they are restored.
                'relative_path' => base_path(),
            ],

            // mysqldump (or sqlite3) must be installed on the server.
            'databases' => [
                env('DB_CONNECTION', 'mysql'),
            ],
        ],

        'database_dump_compressor' => null,
        'database_dump_file_timestamp_format' => null,
        'database_dump_filename_base' => 'database',
        'database_dump_file_extension' => '',

        'destination' => [
            'compression_method' => ZipArchive::CM_DEFAULT,
            'compression_level' => 9,
            'filename_prefix' => '',
            // Production: a disk on another machine (e.g. an S3-compatible bucket, see
            // config/filesystems.php). The default keeps them in storage/app/backups.
            'disks' => [
                env('BACKUP_DISK', 'backups'),
            ],
            'continue_on_failure' => false,
        ],

        'temporary_directory' => storage_path('app/backup-temp'),

        // Set BACKUP_ARCHIVE_PASSWORD to encrypt archives with AES-256: they hold contact
        // messages and the CV.
        'password' => env('BACKUP_ARCHIVE_PASSWORD'),
        'encryption' => 'default',

        'verify_backup' => true,
        'tries' => 2,
        'retry_delay' => 60,
    ],

    // Mail only when something is wrong; a healthy night is silent.
    'notifications' => [
        'notifications' => [
            BackupHasFailedNotification::class => ['mail'],
            UnhealthyBackupWasFoundNotification::class => ['mail'],
            CleanupHasFailedNotification::class => ['mail'],
            BackupWasSuccessfulNotification::class => [],
            HealthyBackupWasFoundNotification::class => [],
            CleanupWasSuccessfulNotification::class => [],
        ],

        'notifiable' => Notifiable::class,

        'mail' => [
            // A list, so an unset address means "no mail" (the package rejects null and '').
            'to' => array_values(array_filter([env('BACKUP_NOTIFICATION_EMAIL') ?: env('PORTFOLIO_NOTIFY_EMAIL')])),
            'from' => [
                'address' => env('MAIL_FROM_ADDRESS', 'hello@example.com'),
                'name' => env('MAIL_FROM_NAME', env('APP_NAME', 'Portfolio')),
            ],
        ],

        'slack' => ['webhook_url' => '', 'channel' => null, 'username' => null, 'icon' => null],
        'discord' => ['webhook_url' => '', 'username' => '', 'avatar_url' => ''],
        'webhook' => ['url' => ''],
    ],

    'log_channel' => null,

    // backup:monitor (daily): unhealthy when the newest backup is older than a day or
    // the backups take more than 5 GB.
    'monitor_backups' => [
        [
            'name' => env('APP_NAME', 'portfolio'),
            'disks' => [env('BACKUP_DISK', 'backups')],
            'health_checks' => [
                MaximumAgeInDays::class => 1,
                MaximumStorageInMegabytes::class => 5000,
            ],
        ],
    ],

    // Keeps every backup from today, one per day for 7 days and one per week for 4 weeks.
    'cleanup' => [
        'strategy' => DefaultStrategy::class,

        'default_strategy' => [
            'keep_all_backups_for_days' => 1,
            'keep_daily_backups_for_days' => 7,
            'keep_weekly_backups_for_weeks' => 4,
            'keep_monthly_backups_for_months' => 0,
            'keep_yearly_backups_for_years' => 0,
            'delete_oldest_backups_when_using_more_megabytes_than' => 5000,
        ],

        'tries' => 1,
        'retry_delay' => 0,
    ],
];
