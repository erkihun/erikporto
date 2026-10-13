<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\FlushesPortfolioCache;
use App\Models\Concerns\HasLocalizedContent;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

final class ProcessStep extends Model
{
    use FlushesPortfolioCache, HasLocalizedContent;

    /** @var list<string> English and Amharic per field; the slug, URLs, tags and enums are not translated. */
    public array $translatable = ['title', 'description'];

    protected $fillable = ['title', 'description', 'sort_order'];

    /** @param Builder<self> $query */
    #[Scope]
    protected function ordered(Builder $query): void
    {
        $query->orderBy('sort_order')->orderBy('id');
    }

    protected function casts(): array
    {
        return ['sort_order' => 'integer'];
    }
}
