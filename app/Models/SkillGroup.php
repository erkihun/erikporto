<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Discipline;
use App\Models\Concerns\FlushesPortfolioCache;
use Database\Factories\SkillGroupFactory;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property Discipline $discipline
 * @property list<string> $items
 */
final class SkillGroup extends Model
{
    /** @use HasFactory<SkillGroupFactory> */
    use FlushesPortfolioCache, HasFactory;

    protected $fillable = ['discipline', 'label', 'items', 'sort_order'];

    /** @param Builder<self> $query */
    #[Scope]
    protected function ordered(Builder $query): void
    {
        $query->orderBy('sort_order')->orderBy('id');
    }

    protected function casts(): array
    {
        return [
            'discipline' => Discipline::class,
            'items' => 'array',
            'sort_order' => 'integer',
        ];
    }
}
