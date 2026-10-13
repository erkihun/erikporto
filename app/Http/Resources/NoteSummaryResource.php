<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Note;
use App\Support\Images\ResponsiveImages;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Keep in sync with `NoteSummary` in resources/js/types/portfolio.ts.
 *
 * @mixin Note
 */
class NoteSummaryResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'slug' => $this->slug,
            'title' => $this->title,
            'excerpt' => $this->excerpt,
            'tags' => $this->tags ?? [],
            'reading_minutes' => $this->reading_minutes,
            'published_at' => $this->published_at?->toIso8601String(),
            'cover' => app(ResponsiveImages::class)->for($this->cover_path),
            'url' => route('notes.show', $this->resource, absolute: false),
        ];
    }
}
