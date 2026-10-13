<?php

declare(strict_types=1);

namespace App\Support;

use App\Enums\Locale;
use Illuminate\Http\Request;

/**
 * URLs for the routes that exist in every locale (see routes/web.php).
 */
final class LocalizedUrls
{
    /** Named GET routes registered once per locale. */
    public const PAGES = ['home', 'projects.show', 'colophon'];

    public static function route(string $name, mixed $parameters = [], ?Locale $locale = null, bool $absolute = false): string
    {
        return route(($locale ?? Locale::current())->routeName($name), $parameters, $absolute);
    }

    /**
     * The current page in every locale, for the language switcher and hreflang.
     * Pages that exist in one language only (notes, /now) link to each locale's home
     * and are marked as having no alternate.
     *
     * @return list<array{code: string, label: string, name: string, hreflang: string, og_locale: string, url: string, current: bool, alternate: bool}>
     */
    public static function alternates(Request $request): array
    {
        $route = $request->route();
        $name = $route?->getName();
        $page = $name === null ? null : (string) preg_replace('/^('.implode('|', array_column(Locale::cases(), 'value')).')\./', '', $name);
        $localized = $page !== null && in_array($page, self::PAGES, true) && $request->isMethod('GET');
        $current = Locale::current();

        return array_map(static fn (Locale $locale): array => [
            'code' => $locale->value,
            'label' => $locale->label(),
            'name' => $locale->nativeName(),
            'hreflang' => $locale->value,
            'og_locale' => $locale->ogLocale(),
            'url' => $localized
                ? self::route($page, $route->parameters(), $locale, absolute: true)
                : self::route('home', [], $locale, absolute: true),
            'current' => $locale === $current,
            'alternate' => $localized,
        ], Locale::cases());
    }
}
