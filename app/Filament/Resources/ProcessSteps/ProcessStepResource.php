<?php

declare(strict_types=1);

namespace App\Filament\Resources\ProcessSteps;

use App\Filament\Resources\ProcessSteps\Pages\ManageProcessSteps;
use App\Models\ProcessStep;
use App\Support\PortfolioCache;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

final class ProcessStepResource extends Resource
{
    protected static ?string $model = ProcessStep::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowPath;

    protected static string|UnitEnum|null $navigationGroup = 'Content';

    protected static ?int $navigationSort = 6;

    protected static ?string $navigationLabel = 'Process';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('title')->required()->maxLength(80),
            Textarea::make('description')->required()->maxLength(300)->rows(3)->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->afterReordering(static fn () => PortfolioCache::flush())
            ->columns([
                TextColumn::make('title')->weight('medium'),
                TextColumn::make('description')->wrap()->limit(120),
            ])
            ->recordActions([EditAction::make(), DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageProcessSteps::route('/')];
    }
}
