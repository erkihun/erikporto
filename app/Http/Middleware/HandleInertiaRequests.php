<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Enums\Locale;
use App\Models\Note;
use App\Models\NowUpdate;
use App\Queries\HomePageQuery;
use App\Support\LocalizedUrls;
use App\Support\PortfolioCache;
use App\Support\Translations;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Middleware;

final class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    /**
     * Closures resolve when the page renders, after SetLocale has run.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'app' => [
                'name' => config('app.name'),
                'url' => config('app.url'),
            ],
            'locale' => fn (): string => Locale::current()->value,
            'locales' => fn (): array => LocalizedUrls::alternates($request),
            'urls' => fn (): array => [
                'home' => LocalizedUrls::route('home'),
                'contact' => LocalizedUrls::route('contact.store'),
                'colophon' => LocalizedUrls::route('colophon'),
                'palette' => LocalizedUrls::route('palette'),
            ],
            // Sent once per visit; switching language is a full page load.
            'translations' => Inertia::once(fn (): array => Translations::for(Locale::current())),
            // Drives the "Notes" nav link; cached, so no query on most requests.
            // Navigation extras fall back to "none" when the database or cache is down, so the
            // error page for that outage still renders (App\Http\Responses\ErrorPage).
            'hasNotes' => fn (): bool => rescue(static fn (): bool => PortfolioCache::remember(
                'nav:has-notes',
                static fn (): string => Note::query()->published()->exists() ? 'yes' : 'no',
            ) === 'yes', false, report: false),
            // Drives the "Now" footer link, the same way.
            'hasNow' => fn (): bool => rescue(static fn (): bool => PortfolioCache::remember(
                'nav:has-now',
                static fn (): string => NowUpdate::query()->published()->exists() ? 'yes' : 'no',
            ) === 'yes', false, report: false),
            // Home sections that have content (nav links to empty ones are hidden).
            'homeSections' => fn (): array => rescue(
                static fn (): array => PortfolioCache::remember('nav:home-sections', static fn (): array => HomePageQuery::sections()),
                ['work', 'about', 'contact'],
                report: false,
            ),
            'flash' => [
                'success' => fn (): ?string => $request->hasSession() ? $request->session()->get('success') : null,
                'error' => fn (): ?string => $request->hasSession() ? $request->session()->get('error') : null,
            ],
        ];
    }
}
