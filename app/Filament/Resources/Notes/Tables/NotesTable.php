<?php

declare(strict_types=1);

namespace App\Filament\Resources\Notes\Tables;

use App\Enums\NoteStatus;
use App\Models\Note;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

final class NotesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('published_at', 'desc')
            ->columns([
                TextColumn::make('title')
                    ->description(static fn (Note $record): string => $record->reading_minutes.' min read')
                    ->searchable()
                    ->weight('medium'),
                TextColumn::make('status')->badge(),
                TextColumn::make('published_at')->label('Publish date')->dateTime('M j, Y H:i')->sortable()->placeholder('—'),
                IconColumn::make('telegram_posted_at')
                    ->label('Telegram')
                    ->state(static fn (Note $record): bool => $record->wasAnnounced())
                    ->boolean(),
                TextColumn::make('tags')->badge()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(NoteStatus::class)
                    ->query(static fn (Builder $query, array $data): Builder => match ($data['value'] ?? null) {
                        NoteStatus::Draft->value => $query->whereNull('published_at'),
                        NoteStatus::Scheduled->value => $query->where('published_at', '>', now()),
                        NoteStatus::Published->value => $query->where('published_at', '<=', now()),
                        default => $query,
                    }),
            ])
            ->recordActions([
                Action::make('publishNow')
                    ->label('Publish now')
                    ->icon('heroicon-o-paper-airplane')
                    ->requiresConfirmation()
                    ->visible(static fn (Note $record): bool => $record->status !== NoteStatus::Published)
                    ->action(static fn (Note $record) => $record->update(['published_at' => now()])),
                Action::make('view')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(static fn (Note $record): string => route('notes.show', $record))
                    ->openUrlInNewTab()
                    ->visible(static fn (Note $record): bool => $record->isLive()),
                EditAction::make(),
            ])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }
}
