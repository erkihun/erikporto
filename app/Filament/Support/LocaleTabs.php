<?php

declare(strict_types=1);

namespace App\Filament\Support;

use App\Enums\Locale;
use Closure;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;

/**
 * One tab per site language for translatable fields (spatie/laravel-translatable).
 * Fields are bound to "title.en" / "title.am"; English is required, Amharic optional:
 * a blank Amharic value is not stored, so the Amharic site falls back to English.
 */
final class LocaleTabs
{
    /**
     * @param  Closure(Locale, bool): array<Component>  $fields  receives the locale and whether it is required
     */
    public static function make(string $label, Closure $fields): Tabs
    {
        return Tabs::make($label)
            ->contained(false)
            ->columnSpanFull()
            ->tabs(array_map(static function (Locale $locale) use ($fields): Tab {
                $required = $locale === Locale::default();

                return Tab::make($locale->nativeName())
                    ->badge($required ? 'Required' : 'Optional')
                    ->badgeColor($required ? 'primary' : 'gray')
                    ->schema($fields($locale, $required));
            }, Locale::cases()));
    }

    /** The form path of a translatable field in one locale: "title.am". */
    public static function path(string $field, Locale $locale): string
    {
        return $field.'.'.$locale->value;
    }

    /**
     * Repeater rows of {value, label} where the label is translatable (profile facts,
     * project metrics). The value (a number) is shared by every language.
     */
    public static function labelledList(string $name, int $valueLength, int $labelLength): Repeater
    {
        return Repeater::make($name)
            ->schema([
                TextInput::make('value')->required()->maxLength($valueLength),
                ...array_map(
                    static fn (Locale $locale): TextInput => TextInput::make(self::path('label', $locale))
                        ->label('Label ('.$locale->nativeName().')')
                        ->required($locale === Locale::default())
                        ->maxLength($labelLength),
                    Locale::cases(),
                ),
            ])
            ->columns(1 + count(Locale::cases()))
            ->reorderable();
    }
}
