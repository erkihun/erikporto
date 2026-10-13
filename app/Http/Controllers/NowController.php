<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Resources\NowUpdateResource;
use App\Models\NowUpdate;
use App\Models\Profile;
use App\Support\PortfolioCache;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class NowController extends Controller
{
    /** Older updates listed under "Previous updates". */
    private const HISTORY = 12;

    public function __invoke(Request $request): Response
    {
        $props = PortfolioCache::remember('now:page', static function () use ($request): ?array {
            $updates = NowUpdate::query()->published()->latestFirst()->limit(self::HISTORY + 1)->get();

            if ($updates->isEmpty()) {
                return null;
            }

            $profile = Profile::current();

            return [
                'current' => (new NowUpdateResource($updates->first()))->resolve($request),
                'previous' => NowUpdateResource::collection($updates->slice(1)->values())->resolve($request),
                'owner' => ['name' => $profile?->name, 'email' => $profile?->email],
            ];
        });

        abort_if($props === null, 404);

        return Inertia::render('Now', $props);
    }
}
