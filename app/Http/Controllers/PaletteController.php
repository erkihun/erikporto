<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Note;
use App\Models\Project;
use App\Support\LocalizedUrls;
use App\Support\PortfolioCache;
use Illuminate\Http\JsonResponse;

/**
 * Content for the ⌘K command palette, fetched the first time it opens (so it costs
 * nothing on page load): published case studies in the current language and recent notes.
 * Mirrors `PaletteData` in resources/js/types/portfolio.ts.
 */
final class PaletteController extends Controller
{
    private const NOTES = 20;

    public function __invoke(): JsonResponse
    {
        $data = PortfolioCache::remember('palette', static fn (): array => [
            'projects' => Project::query()->published()->ordered()->get()->map(static fn (Project $project): array => [
                'title' => $project->title,
                'kind' => $project->category->kind(),
                'url' => LocalizedUrls::route('projects.show', $project),
            ])->all(),
            'notes' => Note::query()->published()->latestFirst()->limit(self::NOTES)->get()->map(static fn (Note $note): array => [
                'title' => $note->title,
                'url' => route('notes.show', $note, absolute: false),
            ])->all(),
        ]);

        return response()->json($data)->header('Cache-Control', 'private, max-age=300');
    }
}
