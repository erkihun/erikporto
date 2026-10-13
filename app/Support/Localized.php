<?php

declare(strict_types=1);

namespace App\Support;

use App\Enums\Locale;

/**
 * Read helpers for translated content. The rule everywhere: the current locale,
 * then English, then any non-blank value, and never an empty string.
 */
final class Localized
{
    /** Picks from a {en, am} map (or a legacy plain string). */
    public static function pick(array|string|null $value): ?string
    {
        if (is_string($value)) {
            return self::text($value);
        }

        $value ??= [];
        foreach ([Locale::current()->value, Locale::default()->value] as $locale) {
            if (filled($value[$locale] ?? null)) {
                return (string) $value[$locale];
            }
        }

        foreach ($value as $text) {
            if (filled($text)) {
                return (string) $text;
            }
        }

        return null;
    }

    /** Blank strings become null, so the UI can hide the element instead of rendering it empty. */
    public static function text(?string $value): ?string
    {
        return filled($value) ? $value : null;
    }

    /**
     * Lists of {value, label} where only the label is translated (profile facts, project metrics).
     *
     * @param  list<array{value?: mixed, label?: array<string, string|null>|string|null}>|null  $items
     * @return list<array{value: string, label: string}>
     */
    public static function labelled(?array $items): array
    {
        $result = [];
        foreach ($items ?? [] as $item) {
            $value = trim((string) ($item['value'] ?? ''));
            $label = self::pick($item['label'] ?? null);
            if ($value !== '' && $label !== null) {
                $result[] = ['value' => $value, 'label' => $label];
            }
        }

        return $result;
    }
}
