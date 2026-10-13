<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Project;
use App\Support\Images\ResponsiveImages;
use App\Support\Localized;
use App\Support\LocalizedUrls;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

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
            'subtitle' => Localized::text($this->subtitle),
            'category' => $this->category->value,
            'kind' => $this->category->kind(),
            'summary' => $this->summary,
            'year' => $this->year,
            'stack' => $this->stack,
            'cover' => app(ResponsiveImages::class)->for($this->cover_path),
            'mock' => $this->mock,
            'url' => LocalizedUrls::route('projects.show', $this->resource),
        ];
    }
}
