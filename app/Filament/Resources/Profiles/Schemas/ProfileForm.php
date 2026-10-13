<?php

declare(strict_types=1);

namespace App\Filament\Resources\Profiles\Schemas;

use App\Enums\Locale;
use App\Filament\Support\LocaleTabs;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

final class ProfileForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([
                Grid::make(1)->columnSpan(2)->schema([
                    Section::make('Hero')->columns(2)->schema([
                        TextInput::make('name')->required()->maxLength(120),
                        Toggle::make('is_available')->label('Available for projects')->inline(false),
                        LocaleTabs::make('Hero copy', static fn (Locale $locale, bool $required): array => [
                            TextInput::make(LocaleTabs::path('headline', $locale))->label('Headline')->required($required)->maxLength(160),
                            Textarea::make(LocaleTabs::path('lede', $locale))
                                ->label('Intro sentence')
                                ->required($required)
                                ->maxLength(400)
                                ->rows(3),
                            TextInput::make(LocaleTabs::path('location', $locale))->label('Location')->maxLength(120),
                        ]),
                    ]),
                    Section::make('About')->schema([
                        LocaleTabs::make('About copy', static fn (Locale $locale, bool $required): array => [
                            TextInput::make(LocaleTabs::path('about_statement', $locale))->label('Statement')->maxLength(240),
                            Repeater::make(LocaleTabs::path('bio', $locale))
                                ->label('Bio paragraphs')
                                ->simple(Textarea::make('paragraph')->required()->rows(3)->maxLength(1200))
                                ->minItems($required ? 1 : 0)
                                ->defaultItems($required ? 1 : 0)
                                ->maxItems(5)
                                ->reorderable(),
                        ]),
                    ]),
                    Section::make('Highlights')
                        ->description('Short facts shown under the hero, e.g. "4" / "Featured projects".')
                        ->schema([
                            LocaleTabs::labelledList('facts', valueLength: 20, labelLength: 60)->maxItems(4),
                        ]),
                ]),
                Grid::make(1)->columnSpan(1)->schema([
                    Section::make('Contact')->schema([
                        TextInput::make('email')->email()->maxLength(190)->helperText('Shown publicly and used for contact notifications.'),
                        Repeater::make('social_links')
                            ->label('Links')
                            ->schema([
                                TextInput::make('label')->required()->maxLength(40),
                                TextInput::make('url')->url()->maxLength(255),
                                TextInput::make('handle')->maxLength(80),
                            ])
                            ->reorderable()
                            ->collapsible()
                            ->itemLabel(static fn (array $state): ?string => $state['label'] ?? null),
                    ]),
                    Section::make('Files')->schema([
                        FileUpload::make('avatar_path')
                            ->label('Portrait')
                            ->image()
                            ->avatar()
                            ->disk('public')
                            ->directory('profile')
                            ->visibility('public')
                            ->maxSize(2048)
                            ->imageEditor(),
                        FileUpload::make('cv_path')
                            ->label('CV (PDF)')
                            ->disk('local') // private; served through the /cv route
                            ->directory('cv')
                            ->acceptedFileTypes(['application/pdf'])
                            ->maxSize(5120)
                            ->downloadable(),
                    ]),
                    Section::make('SEO')->schema([
                        LocaleTabs::make('SEO copy', static fn (Locale $locale): array => [
                            Textarea::make(LocaleTabs::path('meta_description', $locale))
                                ->label('Meta description')
                                ->maxLength(300)
                                ->rows(3)
                                ->helperText('Defaults to the intro sentence.'),
                        ]),
                    ]),
                ]),
            ]);
    }
}
