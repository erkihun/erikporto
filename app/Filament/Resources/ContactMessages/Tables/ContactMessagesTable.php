<?php

declare(strict_types=1);

namespace App\Filament\Resources\ContactMessages\Tables;

use App\Enums\InquiryType;
use App\Models\ContactMessage;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

final class ContactMessagesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                IconColumn::make('read_at')
                    ->label('')
                    ->state(static fn (ContactMessage $record): bool => $record->read_at === null)
                    ->boolean()
                    ->trueIcon('heroicon-s-envelope')
                    ->falseIcon('heroicon-o-envelope-open')
                    ->trueColor('primary')
                    ->falseColor('gray'),
                TextColumn::make('name')->description(static fn (ContactMessage $record): string => $record->email)->searchable(['name', 'email']),
                TextColumn::make('inquiry_type')->label('Type')->badge(),
                TextColumn::make('message')->limit(80)->wrap(),
                TextColumn::make('created_at')->label('Received')->since()->sortable(),
            ])
            ->filters([
                TernaryFilter::make('read_at')
                    ->label('Status')
                    ->placeholder('All')
                    ->trueLabel('Read')
                    ->falseLabel('Unread')
                    ->queries(
                        true: static fn (Builder $query) => $query->whereNotNull('read_at'),
                        false: static fn (Builder $query) => $query->whereNull('read_at'),
                    ),
                SelectFilter::make('inquiry_type')->label('Type')->options(InquiryType::class),
            ])
            ->recordActions([
                ViewAction::make(),
                Action::make('reply')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->url(static fn (ContactMessage $record): string => 'mailto:'.rawurlencode($record->email).'?subject='.rawurlencode('Re: your enquiry')),
                DeleteAction::make(),
            ])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }
}
