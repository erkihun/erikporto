<?php

declare(strict_types=1);

namespace App\Models;

use App\Contracts\AnnouncesOnTelegram;
use App\Models\Concerns\FlushesPortfolioCache;
use App\Models\Concerns\HasImages;
use App\Models\Concerns\HasPublicationDate;
use App\Models\Concerns\QueuesTelegramAnnouncement;
use App\Support\Markdown\MarkdownRenderer;
use Database\Factories\NoteFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Draft / scheduled / published comes from `published_at` (see HasPublicationDate).
 *
 * @property Carbon|null $telegram_posted_at
 * @property list<string>|null $tags
 * @property list<array{id: string, text: string, level: int}>|null $toc
 */
final class Note extends Model implements AnnouncesOnTelegram
{
    /** @use HasFactory<NoteFactory> */
    use FlushesPortfolioCache, HasFactory, HasImages, HasPublicationDate, QueuesTelegramAnnouncement;

    public const TELEGRAM_TOGGLE = 'announce_notes';

    // body_html, toc and reading_minutes are derived on save and never accepted as input.
    protected $fillable = ['slug', 'title', 'excerpt', 'body', 'tags', 'cover_path', 'published_at'];

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function imageAttributes(): array
    {
        return ['cover_path'];
    }

    public function markdownImageAttributes(): array
    {
        return ['body' => 'notes/attachments'];
    }

    /** Re-renders the body (the saving hook below) without touching updated_at. */
    public function refreshRenderedMarkdown(): void
    {
        $this->timestamps = false;
        $this->forceFill(['body_html' => ''])->save();
        $this->timestamps = true;
    }

    protected static function booted(): void
    {
        self::saving(static function (Note $note): void {
            if ($note->isDirty('body') || blank($note->body_html)) {
                $rendered = app(MarkdownRenderer::class)->render((string) $note->body);
                $note->forceFill([
                    'body_html' => $rendered->html,
                    'toc' => $rendered->toc,
                    'reading_minutes' => $rendered->readingMinutes,
                ]);
            }
        });
    }

    public function telegramMessage(): string
    {
        return '<b>'.e($this->title)."</b>\n\n".e($this->excerpt)."\n\n".route('notes.show', $this);
    }

    public function wasAnnounced(): bool
    {
        return $this->telegram_posted_at !== null;
    }

    public function markAnnounced(): void
    {
        $this->forceFill(['telegram_posted_at' => now()])->saveQuietly();
    }

    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
            'telegram_posted_at' => 'datetime',
            'tags' => 'array',
            'toc' => 'array',
            'reading_minutes' => 'integer',
        ];
    }
}
