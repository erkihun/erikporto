<?php

declare(strict_types=1);

namespace App\Support;

/**
 * The self-hosted font files every first view needs (see scripts/fonts.mjs): the
 * unicode-range "critical" Latin files and the Ethiopic core. The layout preloads
 * them so they download alongside the HTML instead of after style calculation.
 */
final class Fonts
{
    /** @var list<string>|null */
    private static ?array $firstView = null;

    /** @return list<string> paths relative to the project root, ready for Vite::asset() */
    public static function firstView(): array
    {
        return self::$firstView ??= array_values(array_map(
            static fn (string $path): string => 'resources/fonts/'.basename($path),
            preg_grep('/-(latin-critical|ethiopic-core|date-latin)\.woff2$/', glob(resource_path('fonts/*.woff2')) ?: []) ?: [],
        ));
    }
}
