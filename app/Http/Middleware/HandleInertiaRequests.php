<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Note;
use App\Models\NowUpdate;
use App\Support\PortfolioCache;
use Illuminate\Http\Request;
use Inertia\Middleware;

final class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    /** @return array<string, mixed> */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'app' => [
                'name' => config('app.name'),
                'url' => config('app.url'),
            ],
            // Drives the "Notes" nav link; cached, so no query on most requests.
            'hasNotes' => fn (): bool => PortfolioCache::remember(
                'nav:has-notes',
                static fn (): string => Note::query()->published()->exists() ? 'yes' : 'no',
            ) === 'yes',
            // Drives the "Now" footer link, the same way.
            'hasNow' => fn (): bool => PortfolioCache::remember(
                'nav:has-now',
                static fn (): string => NowUpdate::query()->published()->exists() ? 'yes' : 'no',
            ) === 'yes',
            'flash' => [
                'success' => fn (): ?string => $request->session()->get('success'),
            ],
        ];
    }
}
