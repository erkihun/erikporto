<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Note;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

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
            'cover_url' => $this->cover_path ? Storage::disk('public')->url($this->cover_path) : null,
            'url' => route('notes.show', $this->resource, absolute: false),
        ];
    }
}
