<?php

declare(strict_types=1);

namespace App\Models;

use App\Contracts\AnnouncesOnTelegram;
use App\Enums\ProjectCategory;
use App\Models\Concerns\FlushesPortfolioCache;
use App\Models\Concerns\QueuesTelegramAnnouncement;
use Database\Factories\ProjectFactory;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property ProjectCategory $category
 * @property list<string> $stack
 * @property list<string>|null $tools
 * @property list<string>|null $gallery
 * @property list<array{value: string, label: string}>|null $metrics
 * @property Carbon|null $telegram_posted_at
 */
final class Project extends Model implements AnnouncesOnTelegram
{
    /** @use HasFactory<ProjectFactory> */
    use FlushesPortfolioCache, HasFactory, QueuesTelegramAnnouncement;

    public const TELEGRAM_TOGGLE = 'announce_projects';

    public const MOCKS = ['id', 'jobs', 'court', 'brand'];

    protected $fillable = [
        'slug', 'title', 'subtitle', 'category', 'summary', 'problem', 'role', 'solution',
        'outcome', 'metrics', 'stack', 'tools', 'repository_url', 'live_url', 'cover_path',
        'gallery', 'mock', 'year', 'is_published', 'sort_order',
    ];

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /** @param Builder<self> $query */
    #[Scope]
    protected function published(Builder $query): void
    {
        $query->where('is_published', true);
    }

    /** @param Builder<self> $query */
    #[Scope]
    protected function ordered(Builder $query): void
    {
        $query->orderBy('sort_order')->orderBy('id');
    }

    public function isLive(): bool
    {
        return $this->is_published;
    }

    public function telegramMessage(): string
    {
        return '<b>New case study: '.e($this->title)."</b>\n\n".e($this->summary)."\n\n".route('projects.show', $this);
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
            'category' => ProjectCategory::class,
            'metrics' => 'array',
            'stack' => 'array',
            'tools' => 'array',
            'gallery' => 'array',
            'year' => 'integer',
            'is_published' => 'boolean',
            'sort_order' => 'integer',
            'telegram_posted_at' => 'datetime',
        ];
    }
}
