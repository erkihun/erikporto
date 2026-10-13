<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ExperienceType;
use App\Models\Concerns\FlushesPortfolioCache;
use App\Models\Concerns\HasLocalizedContent;
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
    use FlushesPortfolioCache, HasFactory, HasLocalizedContent;

    /** @var list<string> English and Amharic per field; the slug, URLs, tags and enums are not translated. */
    public array $translatable = ['title', 'organization', 'location', 'description'];

    protected $fillable = [
        'type', 'title', 'organization', 'location', 'starts_on', 'ends_on', 'description', 'sort_order',
    ];

    /** Human period in the current locale, such as "Mar 2022 — Present"; null when no dates were entered. */
    public function period(): ?string
    {
        if ($this->starts_on === null) {
            return $this->ends_on?->format('Y');
        }

        $month = static fn (Carbon $date): string => $date->locale(app()->getLocale())->translatedFormat('M Y');
        $end = $this->ends_on !== null ? $month($this->ends_on) : __('experience.present');

        return $month($this->starts_on).' — '.$end;
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
