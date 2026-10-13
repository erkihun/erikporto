<?php

declare(strict_types=1);

namespace App\Support;

use App\Enums\Locale;

/**
 * The self-hosted font files every first view needs (see scripts/fonts.mjs): the
 * unicode-range "critical" Latin files and the Ethiopic core, plus the Amharic
 * syllabary on Amharic pages. The layout preloads them so they download alongside
 * the HTML instead of after style calculation.
 */
final class Fonts
{
    /** @var array<string, list<string>> */
    private static array $firstView = [];

    /** @return list<string> paths relative to the project root, ready for Vite::asset() */
    public static function firstView(?Locale $locale = null): array
    {
        $locale ??= Locale::current();
        $pattern = $locale === Locale::Am
            ? '/-(latin-critical|ethiopic-core|ethiopic-amharic|date-latin)\.woff2$/'
            : '/-(latin-critical|ethiopic-core|date-latin)\.woff2$/';

        return self::$firstView[$locale->value] ??= array_values(array_map(
            static fn (string $path): string => 'resources/fonts/'.basename($path),
            preg_grep($pattern, glob(resource_path('fonts/*.woff2')) ?: []) ?: [],
        ));
    }
}
