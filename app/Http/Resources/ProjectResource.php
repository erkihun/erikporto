<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Project;
use App\Support\Images\ResponsiveImages;
use App\Support\Localized;
use Illuminate\Http\Request;

/**
 * Full case study. Keep in sync with `ProjectDetail` in resources/js/types/portfolio.ts.
 *
 * @mixin Project
 */
final class ProjectResource extends ProjectSummaryResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        $images = app(ResponsiveImages::class);
        $images->preload($this->gallery ?? []);

        return [
            ...parent::toArray($request),
            'problem' => $this->problem,
            'role' => $this->role,
            'solution' => $this->solution,
            'outcome' => $this->outcome,
            'metrics' => Localized::labelled($this->metrics),
            'tools' => $this->tools ?? [],
            'repository_url' => $this->repository_url,
            'live_url' => $this->live_url,
            'gallery' => array_values(array_filter(array_map(
                static fn (string $path): ?array => $images->for($path),
                $this->gallery ?? [],
            ))),
        ];
    }
}
