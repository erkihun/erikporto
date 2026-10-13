<?php

declare(strict_types=1);

namespace App\Filament\Resources\Profiles\Tables;

use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

final class ProfilesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->paginated(false)
            ->columns([
                TextColumn::make('name')->weight('medium'),
                TextColumn::make('headline'),
                IconColumn::make('is_available')->label('Available')->boolean(),
                TextColumn::make('updated_at')->since()->label('Updated'),
            ])
            ->recordActions([EditAction::make()]);
    }
}
