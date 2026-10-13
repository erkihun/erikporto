<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Resources\ProjectResource;
use App\Http\Resources\ProjectSummaryResource;
use App\Models\Profile;
use App\Models\Project;
use App\Support\PortfolioCache;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class ProjectController extends Controller
{
    public function show(Request $request, Project $project): Response
    {
        abort_unless($project->is_published, 404);

        $props = PortfolioCache::remember("project:{$project->slug}", static function () use ($request, $project): array {
            $published = Project::query()->published()->ordered()->get();
            $index = $published->search(static fn (Project $p): bool => $p->is($project));
            $next = $published->count() > 1 ? $published->get(($index + 1) % $published->count()) : null;
            $profile = Profile::current();

            return [
                'project' => (new ProjectResource($project))->resolve($request),
                'position' => ['index' => $index + 1, 'total' => $published->count()],
                'next' => $next ? (new ProjectSummaryResource($next))->resolve($request) : null,
                'owner' => ['name' => $profile?->name, 'email' => $profile?->email],
            ];
        });

        return Inertia::render('Projects/Show', $props);
    }
}
