<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ExperienceType;
use App\Models\Concerns\FlushesPortfolioCache;
use Database\Factories\ExperienceFactory;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property ExperienceType $type
 * @property Carbon|null $starts_on
 * @property Carbon|null $ends_on
 */
final class Experience extends Model
{
    /** @use HasFactory<ExperienceFactory> */
    use FlushesPortfolioCache, HasFactory;

    protected $fillable = [
        'type', 'title', 'organization', 'location', 'starts_on', 'ends_on', 'description', 'sort_order',
    ];

    /** Human period such as "Mar 2022 — Present"; null when no dates were entered. */
    public function period(): ?string
    {
        if ($this->starts_on === null) {
            return $this->ends_on?->format('Y');
        }

        $end = $this->ends_on?->format('M Y') ?? 'Present';

        return $this->starts_on->format('M Y').' — '.$end;
    }

    /** @param Builder<self> $query */
    #[Scope]
    protected function ordered(Builder $query): void
    {
        $query->orderBy('sort_order')->orderByDesc('starts_on')->orderBy('id');
    }

    protected function casts(): array
    {
        return [
            'type' => ExperienceType::class,
            'starts_on' => 'date',
            'ends_on' => 'date',
            'sort_order' => 'integer',
        ];
    }
}
