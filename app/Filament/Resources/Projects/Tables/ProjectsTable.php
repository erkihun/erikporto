<?php

declare(strict_types=1);

namespace App\Filament\Resources\Projects\Tables;

use App\Enums\ProjectCategory;
use App\Models\Project;
use App\Support\PortfolioCache;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

final class ProjectsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            // Reordering is a bulk query (no model events), so flush explicitly.
            ->afterReordering(static fn () => PortfolioCache::flush())
            ->columns([
                ImageColumn::make('cover_path')->label('')->disk('public')->imageHeight(40),
                TextColumn::make('title')
                    ->description(static fn (Project $record): ?string => $record->subtitle)
                    ->searchable()
                    ->weight('medium'),
                TextColumn::make('category')->badge(),
                TextColumn::make('year')->sortable()->placeholder('—'),
                ToggleColumn::make('is_published')->label('Published'),
                TextColumn::make('updated_at')->since()->label('Updated')->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('category')->options(ProjectCategory::class),
                TernaryFilter::make('is_published')->label('Published'),
            ])
            ->recordActions([
                Action::make('preview')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(static fn (Project $record): string => route('projects.show', $record))
                    ->openUrlInNewTab()
                    ->visible(static fn (Project $record): bool => $record->is_published),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([DeleteBulkAction::make()]),
            ]);
    }
}
