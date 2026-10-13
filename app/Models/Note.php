<?php

declare(strict_types=1);

namespace App\Models;

use App\Contracts\AnnouncesOnTelegram;
use App\Enums\NoteStatus;
use App\Models\Concerns\FlushesPortfolioCache;
use App\Models\Concerns\QueuesTelegramAnnouncement;
use App\Support\Markdown\MarkdownRenderer;
use Database\Factories\NoteFactory;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property Carbon|null $published_at
 * @property Carbon|null $telegram_posted_at
 * @property list<string>|null $tags
 * @property list<array{id: string, text: string, level: int}>|null $toc
 * @property-read NoteStatus $status
 */
final class Note extends Model implements AnnouncesOnTelegram
{
    /** @use HasFactory<NoteFactory> */
    use FlushesPortfolioCache, HasFactory, QueuesTelegramAnnouncement;

    public const TELEGRAM_TOGGLE = 'announce_notes';

    // body_html, toc and reading_minutes are derived on save and never accepted as input.
    protected $fillable = ['slug', 'title', 'excerpt', 'body', 'tags', 'cover_path', 'published_at'];

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /** Seconds until the next scheduled note goes live, used to expire cached pages on time. */
    public static function secondsUntilNextScheduled(): ?int
    {
        $next = self::query()->where('published_at', '>', now())->min('published_at');

        return $next === null ? null : max(1, (int) ceil(now()->diffInSeconds(Carbon::parse($next))));
    }

    /** @param Builder<self> $query */
    #[Scope]
    protected function published(Builder $query): void
    {
        $query->whereNotNull('published_at')->where('published_at', '<=', now());
    }

    /** @param Builder<self> $query */
    #[Scope]
    protected function latestFirst(Builder $query): void
    {
        $query->orderByDesc('published_at')->orderByDesc('id');
    }

    /** @return Attribute<NoteStatus, never> */
    protected function status(): Attribute
    {
        return Attribute::get(fn (): NoteStatus => NoteStatus::fromPublishedAt($this->published_at));
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

    public function isLive(): bool
    {
        return $this->status === NoteStatus::Published;
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
