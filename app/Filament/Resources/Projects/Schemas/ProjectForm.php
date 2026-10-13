<?php

declare(strict_types=1);

namespace App\Filament\Resources\Projects\Schemas;

use App\Enums\ProjectCategory;
use App\Models\Project;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

final class ProjectForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([
                Grid::make(1)->columnSpan(2)->schema([
                    Section::make('Overview')->columns(2)->schema([
                        TextInput::make('title')
                            ->required()
                            ->maxLength(120)
                            ->live(onBlur: true)
                            ->afterStateUpdated(static function (Get $get, Set $set, ?string $state, string $operation): void {
                                if ($operation === 'create' && blank($get('slug'))) {
                                    $set('slug', Str::slug((string) $state));
                                }
                            }),
                        TextInput::make('slug')
                            ->required()
                            ->maxLength(120)
                            ->alphaDash()
                            ->unique(ignoreRecord: true)
                            ->helperText('Used in the URL: /work/{slug}'),
                        TextInput::make('subtitle')->maxLength(200)->columnSpanFull(),
                        Textarea::make('summary')
                            ->required()
                            ->maxLength(300)
                            ->rows(2)
                            ->helperText('One sentence shown on the project card.')
                            ->columnSpanFull(),
                    ]),
                    Section::make('Case study')
                        ->description('Problem → role → solution → outcome. Keep each part short and concrete.')
                        ->schema([
                            Textarea::make('problem')->required()->rows(3)->maxLength(3000),
                            Textarea::make('role')->label('My role')->required()->rows(2)->maxLength(2000),
                            Textarea::make('solution')->required()->rows(4)->maxLength(4000),
                            Textarea::make('outcome')->required()->rows(2)->maxLength(2000),
                            Repeater::make('metrics')
                                ->label('Measured results')
                                ->helperText('Verified numbers only, e.g. "1,200" / "employee records migrated".')
                                ->schema([
                                    TextInput::make('value')->required()->maxLength(20),
                                    TextInput::make('label')->required()->maxLength(80),
                                ])
                                ->columns(2)
                                ->maxItems(4)
                                ->defaultItems(0)
                                ->reorderable(),
                        ]),
                    Section::make('Media')->schema([
                        FileUpload::make('cover_path')
                            ->label('Cover screenshot')
                            ->image()
                            ->disk('public')
                            ->directory('projects/covers')
                            ->visibility('public')
                            ->maxSize(4096)
                            ->imageEditor()
                            ->helperText('16:10 works best. Without a cover, the illustrated placeholder below is shown.'),
                        FileUpload::make('gallery')
                            ->image()
                            ->multiple()
                            ->reorderable()
                            ->disk('public')
                            ->directory('projects/gallery')
                            ->visibility('public')
                            ->maxSize(4096)
                            ->maxFiles(12),
                        Select::make('mock')
                            ->label('Placeholder illustration')
                            ->options(array_combine(Project::MOCKS, ['Digital ID & org chart', 'Job listings', 'Case dashboard', 'Brand board'])),
                    ]),
                ]),
                Grid::make(1)->columnSpan(1)->schema([
                    Section::make('Publishing')->schema([
                        Toggle::make('is_published')->label('Published')->default(false),
                        Select::make('category')
                            ->options(ProjectCategory::class)
                            ->required()
                            ->default(ProjectCategory::Software),
                        TextInput::make('year')->numeric()->minValue(2000)->maxValue((int) date('Y') + 1),
                        TextInput::make('sort_order')->numeric()->default(0)->helperText('Or drag rows in the list.'),
                    ]),
                    Section::make('Stack & links')->schema([
                        TagsInput::make('stack')->required()->placeholder('Add technology')->helperText('Shown as tags.'),
                        TagsInput::make('tools')->placeholder('e.g. Illustrator, Figma'),
                        TextInput::make('repository_url')->label('Repository')->url()->maxLength(255)->prefixIcon('heroicon-o-code-bracket'),
                        TextInput::make('live_url')->label('Live demo')->url()->maxLength(255)->prefixIcon('heroicon-o-globe-alt'),
                    ]),
                ]),
            ]);
    }
}
