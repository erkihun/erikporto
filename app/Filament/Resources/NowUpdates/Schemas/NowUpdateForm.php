<?php

declare(strict_types=1);

namespace App\Filament\Resources\NowUpdates\Schemas;

use App\Enums\NoteStatus;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Support\Carbon;

final class NowUpdateForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([
                Grid::make(1)->columnSpan(2)->schema([
                    Section::make()->schema([
                        MarkdownEditor::make('body')
                            ->label('What you are doing now')
                            ->required()
                            ->minHeight('20rem')
                            ->fileAttachmentsDisk('public')
                            ->fileAttachmentsDirectory('now/attachments')
                            ->helperText('Markdown. A few short paragraphs or a list. Raw HTML is shown as text, not rendered.'),
                    ]),
                ]),
                Grid::make(1)->columnSpan(1)->schema([
                    Section::make('Publishing')->schema([
                        DateTimePicker::make('published_at')
                            ->label('Publish at')
                            ->seconds(false)
                            ->native(false)
                            ->live()
                            ->helperText('Empty = draft. A future time schedules it; /now switches over automatically.'),
                        TextEntry::make('status_preview')
                            ->label('Status')
                            ->badge()
                            ->state(static fn (Get $get): NoteStatus => NoteStatus::fromPublishedAt(
                                filled($get('published_at')) ? Carbon::parse($get('published_at')) : null,
                            )),
                    ]),
                ]),
            ]);
    }
}
