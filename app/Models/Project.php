<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ProjectCategory;
use App\Models\Concerns\FlushesPortfolioCache;
use Database\Factories\ProjectFactory;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property ProjectCategory $category
 * @property list<string> $stack
 * @property list<string>|null $tools
 * @property list<string>|null $gallery
 * @property list<array{value: string, label: string}>|null $metrics
 */
final class Project extends Model
{
    /** @use HasFactory<ProjectFactory> */
    use FlushesPortfolioCache, HasFactory;

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
        ];
    }
}
