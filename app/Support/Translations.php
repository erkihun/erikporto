<?php

declare(strict_types=1);

namespace App\Support;

use App\Enums\Locale;

/**
 * The UI strings for one locale, as shared with the frontend (`useTrans()`).
 * Read from lang/{locale}.json; any missing or empty string falls back to English.
 */
final class Translations
{
    /** @var array<string, array<string, string>> */
    private static array $loaded = [];

    /** @return array<string, string> */
    public static function for(Locale $locale): array
    {
        return self::$loaded[$locale->value] ??= array_merge(
            self::read(Locale::default()),
            array_filter(self::read($locale), static fn (string $text): bool => $text !== ''),
        );
    }

    /** @return array<string, string> */
    private static function read(Locale $locale): array
    {
        $path = lang_path($locale->value.'.json');

        return is_file($path) ? (array) json_decode((string) file_get_contents($path), true, flags: JSON_THROW_ON_ERROR) : [];
    }
}
