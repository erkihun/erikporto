<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Public site languages. English lives at `/`, Amharic under `/am`.
 */
enum Locale: string
{
    case En = 'en';
    case Am = 'am';

    public static function default(): self
    {
        return self::En;
    }

    public static function current(): self
    {
        return self::tryFrom(app()->getLocale()) ?? self::default();
    }

    /** The locale a URL path belongs to, from its first segment ("am/work/x" → Am). */
    public static function fromPath(string $path): self
    {
        $segment = explode('/', trim($path, '/'))[0];

        return $segment !== '' && $segment !== self::default()->value ? (self::tryFrom($segment) ?? self::default()) : self::default();
    }

    /** URL prefix: none for the default locale. */
    public function prefix(): string
    {
        return $this === self::default() ? '' : $this->value;
    }

    /** Route name in this locale: "home" or "am.home". */
    public function routeName(string $name): string
    {
        return $this === self::default() ? $name : $this->value.'.'.$name;
    }

    /** Short label for the language switcher. */
    public function label(): string
    {
        return match ($this) {
            self::En => 'EN',
            self::Am => 'አማ',
        };
    }

    public function nativeName(): string
    {
        return match ($this) {
            self::En => 'English',
            self::Am => 'አማርኛ',
        };
    }

    /** Open Graph locale. */
    public function ogLocale(): string
    {
        return match ($this) {
            self::En => 'en_US',
            self::Am => 'am_ET',
        };
    }
}
