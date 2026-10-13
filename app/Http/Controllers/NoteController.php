<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Resources\NoteResource;
use App\Http\Resources\NoteSummaryResource;
use App\Models\Note;
use App\Models\Profile;
use App\Support\PortfolioCache;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class NoteController extends Controller
{
    private const PER_PAGE = 12;

    public function index(Request $request): Response
    {
        $page = max(1, $request->integer('page', 1));

        $props = PortfolioCache::remember("notes:page:{$page}", static function () use ($request, $page): array {
            $paginator = Note::query()->published()->latestFirst()->simplePaginate(self::PER_PAGE, page: $page);

            return [
                'notes' => NoteSummaryResource::collection($paginator->items())->resolve($request),
                'pagination' => [
                    'page' => $paginator->currentPage(),
                    'prev' => $paginator->previousPageUrl() ? route('notes.index', ['page' => $page - 1], false) : null,
                    'next' => $paginator->hasMorePages() ? route('notes.index', ['page' => $page + 1], false) : null,
                ],
                'owner' => self::owner(),
            ];
        });

        abort_if($page > 1 && $props['notes'] === [], 404);

        return Inertia::render('Notes/Index', $props);
    }

    public function show(Request $request, Note $note): Response
    {
        abort_unless($note->isLive(), 404);

        $props = PortfolioCache::remember("note:{$note->slug}", static function () use ($request, $note): array {
            $older = Note::query()->published()->latestFirst()
                ->where(fn ($q) => $q->where('published_at', '<', $note->published_at)
                    ->orWhere(fn ($q) => $q->where('published_at', $note->published_at)->where('id', '<', $note->id)))
                ->first();
            $newer = Note::query()->published()
                ->where(fn ($q) => $q->where('published_at', '>', $note->published_at)
                    ->orWhere(fn ($q) => $q->where('published_at', $note->published_at)->where('id', '>', $note->id)))
                ->orderBy('published_at')->orderBy('id')
                ->first();

            return [
                'note' => (new NoteResource($note))->resolve($request),
                'older' => $older ? (new NoteSummaryResource($older))->resolve($request) : null,
                'newer' => $newer ? (new NoteSummaryResource($newer))->resolve($request) : null,
                'owner' => self::owner(),
            ];
        });

        return Inertia::render('Notes/Show', $props);
    }

    /** @return array{name: string|null, email: string|null} */
    private static function owner(): array
    {
        $profile = Profile::current();

        return ['name' => $profile?->name, 'email' => $profile?->email];
    }
}
