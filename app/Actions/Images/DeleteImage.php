<?php

declare(strict_types=1);

namespace App\Actions\Images;

use App\Models\ImageAsset;
use Illuminate\Support\Facades\Storage;

/** Removes an image everywhere: the original, every variant and its row. */
final readonly class DeleteImage
{
    public function handle(string $path, string $disk = 'public'): void
    {
        $asset = ImageAsset::query()->where('path', $path)->first();

        Storage::disk($asset !== null ? $asset->disk : $disk)->delete($asset?->files() ?? [$path]);
        $asset?->delete();
    }
}
