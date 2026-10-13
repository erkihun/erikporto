<?php

declare(strict_types=1);

namespace App\Filament\Resources\Experiences;

use App\Enums\ExperienceType;
use App\Filament\Resources\Experiences\Pages\ManageExperiences;
use App\Models\Experience;
use App\Support\PortfolioCache;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

final class ExperienceResource extends Resource
{
    protected static ?string $model = Experience::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBriefcase;

    protected static string|UnitEnum|null $navigationGroup = 'Content';

    protected static ?int $navigationSort = 5;

    protected static ?string $navigationLabel = 'Experience & education';

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(2)->components([
            Select::make('type')->options(ExperienceType::class)->required()->default(ExperienceType::Work),
            TextInput::make('title')->label('Role or degree')->required()->maxLength(140),
            TextInput::make('organization')->label('Organisation or school')->required()->maxLength(160),
            TextInput::make('location')->maxLength(120),
            DatePicker::make('starts_on')->label('Start')->native(false)->displayFormat('M Y'),
            DatePicker::make('ends_on')
                ->label('End')
                ->native(false)
                ->displayFormat('M Y')
                ->afterOrEqual('starts_on')
                ->helperText('Leave empty if ongoing.'),
            Textarea::make('description')->rows(3)->maxLength(1000)->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->afterReordering(static fn () => PortfolioCache::flush())
            ->columns([
                TextColumn::make('type')->badge(),
                TextColumn::make('title')->description(static fn (Experience $record): string => $record->organization)->weight('medium'),
                TextColumn::make('period')
                    ->state(static fn (Experience $record): ?string => $record->period())
                    ->placeholder('Dates missing'),
            ])
            ->recordActions([EditAction::make(), DeleteAction::make()])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageExperiences::route('/')];
    }
}
