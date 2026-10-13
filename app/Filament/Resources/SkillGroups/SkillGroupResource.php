<?php

declare(strict_types=1);

namespace App\Filament\Resources\SkillGroups;

use App\Enums\Discipline;
use App\Filament\Resources\SkillGroups\Pages\ManageSkillGroups;
use App\Models\SkillGroup;
use App\Support\PortfolioCache;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;

final class SkillGroupResource extends Resource
{
    protected static ?string $model = SkillGroup::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedWrenchScrewdriver;

    protected static string|UnitEnum|null $navigationGroup = 'Content';

    protected static ?int $navigationSort = 3;

    protected static ?string $navigationLabel = 'Capabilities';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('discipline')->options(Discipline::class)->required(),
            TextInput::make('label')->required()->maxLength(60),
            TagsInput::make('items')->required()->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->afterReordering(static fn () => PortfolioCache::flush())
            ->defaultGroup('discipline')
            ->columns([
                TextColumn::make('label')->weight('medium'),
                TextColumn::make('items')->badge()->separator(','),
            ])
            ->filters([SelectFilter::make('discipline')->options(Discipline::class)])
            ->recordActions([EditAction::make(), DeleteAction::make()])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageSkillGroups::route('/')];
    }
}
