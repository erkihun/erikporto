<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/**
 * Card-sized projection. Keep in sync with `ProjectSummary` in resources/js/types/portfolio.ts.
 *
 * @mixin Project
 */
class ProjectSummaryResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'slug' => $this->slug,
            'title' => $this->title,
            'subtitle' => $this->subtitle,
            'category' => $this->category->value,
            'kind' => $this->category->kind(),
            'summary' => $this->summary,
            'year' => $this->year,
            'stack' => $this->stack,
            'cover_url' => $this->cover_path ? Storage::disk('public')->url($this->cover_path) : null,
            'mock' => $this->mock,
            'url' => route('projects.show', $this->resource, absolute: false),
        ];
    }
}
