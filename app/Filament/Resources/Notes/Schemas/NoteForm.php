<?php

declare(strict_types=1);

namespace App\Filament\Resources\Notes\Schemas;

use App\Enums\NoteStatus;
use App\Models\Note;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

final class NoteForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([
                Grid::make(1)->columnSpan(2)->schema([
                    Section::make()->schema([
                        TextInput::make('title')
                            ->required()
                            ->maxLength(160)
                            ->live(onBlur: true)
                            ->afterStateUpdated(static function (Get $get, Set $set, ?string $state, string $operation): void {
                                if ($operation === 'create' && blank($get('slug'))) {
                                    $set('slug', Str::slug((string) $state));
                                }
                            }),
                        TextInput::make('slug')
                            ->required()
                            ->maxLength(140)
                            ->alphaDash()
                            ->unique(ignoreRecord: true)
                            ->helperText('URL: /notes/{slug}. Changing it after publishing breaks shared links.'),
                        Textarea::make('excerpt')
                            ->required()
                            ->maxLength(300)
                            ->rows(2)
                            ->helperText('Shown in lists, the RSS feed, link previews and the Telegram post.'),
                        MarkdownEditor::make('body')
                            ->required()
                            ->minHeight('28rem')
                            ->fileAttachmentsDisk('public')
                            ->fileAttachmentsDirectory('notes/attachments')
                            ->helperText('Markdown. Raw HTML is shown as text, not rendered. Use ## and ### headings to build the table of contents.'),
                    ]),
                ]),
                Grid::make(1)->columnSpan(1)->schema([
                    Section::make('Publishing')->schema([
                        DateTimePicker::make('published_at')
                            ->label('Publish at')
                            ->seconds(false)
                            ->native(false)
                            ->live()
                            ->helperText('Empty = draft. A future time schedules it; the site and Telegram update automatically.'),
                        TextEntry::make('status_preview')
                            ->label('Status')
                            ->badge()
                            ->state(static fn (Get $get): NoteStatus => NoteStatus::fromPublishedAt(
                                filled($get('published_at')) ? Carbon::parse($get('published_at')) : null,
                            )),
                        TextEntry::make('telegram_posted_at')
                            ->label('Posted to Telegram')
                            ->dateTime()
                            ->placeholder('Not yet')
                            ->visible(static fn (?Note $record): bool => $record !== null),
                    ]),
                    Section::make('Details')->schema([
                        TagsInput::make('tags')->placeholder('Add tag'),
                        FileUpload::make('cover_path')
                            ->label('Cover image')
                            ->image()
                            ->disk('public')
                            ->directory('notes/covers')
                            ->visibility('public')
                            ->maxSize(4096),
                    ]),
                ]),
            ]);
    }
}
