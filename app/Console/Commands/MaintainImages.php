<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\Images\DeleteImage;
use App\Actions\Images\RegisterImage;
use App\Jobs\ProcessUploadedImage;
use App\Models\ImageAsset;
use App\Support\Images\ImageReferences;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Console\Attribute\AsCommand;

/**
 * Daily housekeeping for uploads (routes/console.php):
 * - registers and processes images that have no variants yet (uploads from before the
 *   pipeline existed, or a job that failed for good);
 * - deletes images nothing uses any more, and files in the upload directories that were
 *   never saved to a record (an editor that uploaded and then cancelled). Only files older
 *   than a day are touched, so an upload in progress is never removed.
 */
#[AsCommand(name: 'portfolio:images', description: 'Process unprocessed uploads and delete orphaned images')]
final class MaintainImages extends Command
{
    protected $signature = 'portfolio:images {--dry-run : List what would change without changing it}';

    private const GRACE_SECONDS = 86400;

    public function handle(RegisterImage $register, DeleteImage $delete): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $inUse = ImageReferences::inUse();
        $disk = Storage::disk('public');
        $cutoff = time() - self::GRACE_SECONDS;

        $processed = 0;
        foreach (array_keys($inUse) as $path) {
            $asset = ImageAsset::query()->where('path', $path)->first();
            if ($asset?->processed_at !== null) {
                continue;
            }
            $processed++;
            if ($dryRun) {
                $this->line("process  {$path}");

                continue;
            }
            $asset ??= $register->handle($path);
            if ($asset !== null && $asset->processed_at === null) {
                ProcessUploadedImage::dispatch($asset->id);
            }
        }

        $deleted = 0;
        $known = [];
        foreach (ImageAsset::query()->get() as $asset) {
            array_push($known, ...$asset->files());
            if (isset($inUse[$asset->path]) || $asset->updated_at?->getTimestamp() > $cutoff) {
                continue;
            }
            $deleted++;
            $this->line("delete   {$asset->path}");
            if (! $dryRun) {
                $delete->handle($asset->path, $asset->disk);
            }
        }

        $known = array_flip($known);
        foreach (ImageReferences::DIRECTORIES as $directory) {
            foreach ([...$disk->files($directory), ...$disk->files($directory.'/variants')] as $file) {
                if (isset($known[$file]) || isset($inUse[$file]) || $disk->lastModified($file) > $cutoff) {
                    continue;
                }
                $deleted++;
                $this->line("delete   {$file}");
                if (! $dryRun) {
                    $disk->delete($file);
                }
            }
        }

        $this->components->info(($dryRun ? 'Dry run: ' : '')."{$processed} to process, {$deleted} orphaned file(s) deleted.");

        return self::SUCCESS;
    }
}
