<?php

declare(strict_types=1);

namespace App\Filament\Resources\NowUpdates\Tables;

use App\Enums\NoteStatus;
use App\Models\NowUpdate;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

final class NowUpdatesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('published_at', 'desc')
            ->columns([
                TextColumn::make('published_at')->label('Publish date')->dateTime('M j, Y H:i')->sortable()->placeholder('—'),
                TextColumn::make('status')->badge(),
                TextColumn::make('summary')
                    ->label('Update')
                    ->state(static fn (NowUpdate $record): string => $record->summary(100))
                    ->wrap(),
            ])
            ->recordActions([
                Action::make('publishNow')
                    ->label('Publish now')
                    ->icon('heroicon-o-paper-airplane')
                    ->requiresConfirmation()
                    ->visible(static fn (NowUpdate $record): bool => $record->status !== NoteStatus::Published)
                    ->action(static fn (NowUpdate $record) => $record->update(['published_at' => now()])),
                Action::make('view')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(route('now'))
                    ->openUrlInNewTab()
                    ->visible(static fn (NowUpdate $record): bool => $record->isLive()),
                EditAction::make(),
            ])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }
}
