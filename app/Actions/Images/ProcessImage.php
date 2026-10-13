<?php

declare(strict_types=1);

namespace App\Actions\Images;

use App\Models\ImageAsset;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;
use Intervention\Image\Interfaces\ImageInterface;

/**
 * Rotates the upload by its EXIF orientation, rewrites it without metadata (GD never
 * writes EXIF, so GPS and camera data are gone), and writes WebP variants at the
 * responsive widths that are smaller than the original. Safe to run again.
 */
final readonly class ProcessImage
{
    /** Widths for srcset; images narrower than 640 get a single WebP copy at their own width. */
    public const WIDTHS = [640, 1280, 1920];

    private const QUALITY = 82;

    public function handle(ImageAsset $asset): ImageAsset
    {
        $storage = Storage::disk($asset->disk);
        $manager = ImageManager::gd(autoOrientation: true, decodeAnimation: false, strip: true);
        $image = $manager->read((string) $storage->get($asset->path));

        $storage->put($asset->path, (string) $this->encodeOriginal($image, $asset->path));

        $variants = [];
        foreach (self::widthsFor($image->width()) as $width) {
            $variant = (clone $image)->scaleDown(width: $width);
            $path = self::variantPath($asset->path, $width);
            $storage->put($path, (string) $variant->toWebp(quality: self::QUALITY));
            $variants[] = ['width' => $variant->width(), 'height' => $variant->height(), 'path' => $path];
        }

        // Variants from an earlier run that no longer apply.
        $kept = array_column($variants, 'path');
        $storage->delete(array_values(array_diff(array_column($asset->variants ?? [], 'path'), $kept)));

        $asset->forceFill([
            'width' => $image->width(),
            'height' => $image->height(),
            'variants' => $variants,
            'processed_at' => now(),
        ])->save();

        return $asset;
    }

    /** @return list<int> */
    public static function widthsFor(int $originalWidth): array
    {
        $widths = array_values(array_filter(self::WIDTHS, static fn (int $width): bool => $width < $originalWidth));

        return $widths === [] ? [$originalWidth] : $widths;
    }

    /** "projects/covers/abc.jpg" → "projects/covers/variants/abc-640.webp" */
    public static function variantPath(string $path, int $width): string
    {
        $info = pathinfo($path);
        $directory = ($info['dirname'] ?? '.') === '.' ? '' : $info['dirname'].'/';

        return $directory.'variants/'.$info['filename'].'-'.$width.'.webp';
    }

    private function encodeOriginal(ImageInterface $image, string $path): string
    {
        return (string) match (strtolower(pathinfo($path, PATHINFO_EXTENSION))) {
            'png' => $image->toPng(),
            'webp' => $image->toWebp(quality: 90),
            default => $image->toJpeg(quality: 88, progressive: true),
        };
    }
}
