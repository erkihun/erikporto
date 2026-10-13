<?php

declare(strict_types=1);

namespace App\Support\Images;

use App\Models\ImageAsset;
use Illuminate\Support\Facades\Storage;

/**
 * Image props for Resources (mirrored as ResponsiveImage in resources/js/types/portfolio.ts).
 * Scoped to the request: preload() fetches the rows for a whole page in one query.
 */
final class ResponsiveImages
{
    /** @var array<string, ImageAsset|null> */
    private array $assets = [];

    /** @param  iterable<string|null>  $paths */
    public function preload(iterable $paths): void
    {
        $missing = [];
        foreach ($paths as $path) {
            if (is_string($path) && $path !== '' && ! array_key_exists($path, $this->assets)) {
                $missing[$path] = true;
            }
        }

        if ($missing === []) {
            return;
        }

        $found = ImageAsset::query()->whereIn('path', array_keys($missing))->get()->keyBy('path');
        foreach (array_keys($missing) as $path) {
            $this->assets[$path] = $found->get($path);
        }
    }

    /**
     * The original as src, the WebP variants as srcset (once processed) and the intrinsic size.
     *
     * @return array{url: string, srcset: string|null, width: int|null, height: int|null}|null
     */
    public function for(?string $path): ?array
    {
        if ($path === null || $path === '') {
            return null;
        }

        $this->preload([$path]);
        $asset = $this->assets[$path];

        return [
            'url' => Storage::disk('public')->url($path),
            'srcset' => $asset?->srcset(),
            'width' => $asset?->width,
            'height' => $asset?->height,
        ];
    }
}
