<?php

declare(strict_types=1);

namespace App\Filament\Resources\NowUpdates;

use App\Filament\Resources\NowUpdates\Pages\CreateNowUpdate;
use App\Filament\Resources\NowUpdates\Pages\EditNowUpdate;
use App\Filament\Resources\NowUpdates\Pages\ListNowUpdates;
use App\Filament\Resources\NowUpdates\Schemas\NowUpdateForm;
use App\Filament\Resources\NowUpdates\Tables\NowUpdatesTable;
use App\Models\NowUpdate;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

/** Entries of the /now page; the newest published one is shown, older ones become its history. */
final class NowUpdateResource extends Resource
{
    protected static ?string $model = NowUpdate::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClock;

    protected static string|UnitEnum|null $navigationGroup = 'Content';

    protected static ?int $navigationSort = 7;

    protected static ?string $navigationLabel = 'Now';

    protected static ?string $modelLabel = 'now update';

    public static function getRecordTitle(?Model $record): string|Htmlable|null
    {
        return $record instanceof NowUpdate
            ? ($record->published_at?->format('M j, Y') ?? 'Draft').' update'
            : null;
    }

    public static function form(Schema $schema): Schema
    {
        return NowUpdateForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return NowUpdatesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListNowUpdates::route('/'),
            'create' => CreateNowUpdate::route('/create'),
            'edit' => EditNowUpdate::route('/{record}/edit'),
        ];
    }
}
