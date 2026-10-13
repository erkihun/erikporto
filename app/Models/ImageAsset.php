<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

/**
 * An uploaded image and its responsive WebP variants (see App\Actions\Images).
 * Rows are written only by the image pipeline, never from user input.
 *
 * @property int $id
 * @property string $disk
 * @property string $path
 * @property int $width
 * @property int $height
 * @property list<array{width: int, height: int, path: string}>|null $variants
 * @property Carbon|null $processed_at
 */
final class ImageAsset extends Model
{
    protected $fillable = [];

    public function url(): string
    {
        return Storage::disk($this->disk)->url($this->path);
    }

    /** "…-640.webp 640w, …-1280.webp 1280w"; null until the variants exist. */
    public function srcset(): ?string
    {
        if ($this->processed_at === null || $this->variants === null || $this->variants === []) {
            return null;
        }

        $disk = Storage::disk($this->disk);

        return implode(', ', array_map(
            static fn (array $variant): string => $disk->url($variant['path']).' '.$variant['width'].'w',
            $this->variants,
        ));
    }

    /** @return list<string> the original plus every variant file */
    public function files(): array
    {
        return [$this->path, ...array_column($this->variants ?? [], 'path')];
    }

    protected function casts(): array
    {
        return [
            'width' => 'integer',
            'height' => 'integer',
            'variants' => 'array',
            'processed_at' => 'datetime',
        ];
    }
}
