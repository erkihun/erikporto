<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\FlushesPortfolioCache;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

final class ProcessStep extends Model
{
    use FlushesPortfolioCache;

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
