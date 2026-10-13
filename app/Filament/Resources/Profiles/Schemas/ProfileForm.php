<?php

declare(strict_types=1);

namespace App\Filament\Resources\Profiles\Schemas;

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
                        TextInput::make('headline')->required()->maxLength(160),
                        Textarea::make('lede')
                            ->label('Intro sentence')
                            ->required()
                            ->maxLength(400)
                            ->rows(3)
                            ->columnSpanFull(),
                        Toggle::make('is_available')->label('Available for projects')->inline(false),
                        TextInput::make('location')->maxLength(120),
                    ]),
                    Section::make('About')->schema([
                        TextInput::make('about_statement')->label('Statement')->maxLength(240),
                        Repeater::make('bio')
                            ->label('Bio paragraphs')
                            ->simple(Textarea::make('paragraph')->required()->rows(3)->maxLength(1200))
                            ->minItems(1)
                            ->maxItems(5)
                            ->reorderable(),
                    ]),
                    Section::make('Highlights')
                        ->description('Short facts shown under the hero, e.g. "4" / "Featured projects".')
                        ->schema([
                            Repeater::make('facts')
                                ->schema([
                                    TextInput::make('value')->required()->maxLength(20),
                                    TextInput::make('label')->required()->maxLength(60),
                                ])
                                ->columns(2)
                                ->maxItems(4)
                                ->reorderable(),
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
                        Textarea::make('meta_description')->maxLength(300)->rows(3)->helperText('Defaults to the intro sentence.'),
                    ]),
                ]),
            ]);
    }
}
