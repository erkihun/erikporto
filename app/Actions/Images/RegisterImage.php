<?php

declare(strict_types=1);

namespace App\Actions\Images;

use App\Jobs\ProcessUploadedImage;
use App\Models\ImageAsset;
use Illuminate\Support\Facades\Storage;

/**
 * Records a freshly uploaded image synchronously (so pages can render width/height
 * at once) and queues the slow part: EXIF stripping and WebP variants.
 */
final readonly class RegisterImage
{
    public function handle(string $path, string $disk = 'public'): ?ImageAsset
    {
        $existing = ImageAsset::query()->where('path', $path)->first();
        if ($existing !== null) {
            return $existing;
        }

        $storage = Storage::disk($disk);
        if (! $storage->exists($path)) {
            return null;
        }

        $contents = (string) $storage->get($path);
        $size = @getimagesizefromstring($contents);
        if ($size === false || ! in_array($size[2], [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP], true)) {
            return null;
        }

        [$width, $height] = $size;
        if (self::isRotatedSideways($contents)) {
            [$width, $height] = [$height, $width];
        }

        $asset = new ImageAsset;
        $asset->forceFill(['disk' => $disk, 'path' => $path, 'width' => $width, 'height' => $height])->save();

        ProcessUploadedImage::dispatch($asset->id);

        return $asset;
    }

    /** EXIF orientations 5-8 rotate by 90°: the displayed image is taller than it is stored. */
    private static function isRotatedSideways(string $contents): bool
    {
        if (! function_exists('exif_read_data') || ! str_starts_with($contents, "\xFF\xD8")) {
            return false;
        }

        $exif = @exif_read_data('data://image/jpeg;base64,'.base64_encode($contents));

        return is_array($exif) && in_array((int) ($exif['Orientation'] ?? 1), [5, 6, 7, 8], true);
    }
}
