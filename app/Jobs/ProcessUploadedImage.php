<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Actions\Images\ProcessImage;
use App\Models\ImageAsset;
use App\Support\Images\ImageReferences;
use App\Support\PortfolioCache;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Strips and resizes one uploaded image (App\Actions\Images\ProcessImage), then
 * refreshes everything that shows it: cached pages and Markdown bodies, whose
 * rendered <img> gains its srcset once the variants exist.
 */
final class ProcessUploadedImage implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /** @var list<int> */
    public array $backoff = [10, 60];

    public int $uniqueFor = 600;

    public function __construct(public readonly int $assetId)
    {
        $this->afterCommit();
    }

    public function uniqueId(): string
    {
        return (string) $this->assetId;
    }

    public function handle(ProcessImage $process): void
    {
        $asset = ImageAsset::query()->find($this->assetId);
        if ($asset === null) {
            return; // deleted before the worker got to it
        }

        $process->handle($asset);

        ImageReferences::rerenderMarkdownUsing($asset->path);
        PortfolioCache::flush();
    }
}
