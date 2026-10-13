<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\Locale;
use App\Models\Note;
use App\Models\NowUpdate;
use App\Models\Project;
use App\Support\LocalizedUrls;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;

final class SitemapController extends Controller
{
    public function __invoke(): Response
    {
        $projects = Project::query()->published()->ordered()->get(['id', 'slug', 'updated_at']);

        return response()
            ->view('sitemap', [
                // Pages in every language, each listed once per locale with hreflang alternates.
                'localized' => [
                    $this->localized('home'),
                    ...$projects->map(fn (Project $project): array => $this->localized('projects.show', $project, $project->updated_at)),
                ],
                'notes' => Note::query()->published()->latestFirst()->get(['slug', 'updated_at']),
                'now' => NowUpdate::query()->published()->latestFirst()->first(['updated_at']),
            ])
            ->header('Content-Type', 'application/xml');
    }

    /** @return array{urls: array<string, string>, lastmod: ?Carbon} */
    private function localized(string $name, mixed $parameters = [], ?Carbon $lastmod = null): array
    {
        $urls = [];
        foreach (Locale::cases() as $locale) {
            $urls[$locale->value] = LocalizedUrls::route($name, $parameters, $locale, absolute: true);
        }

        return ['urls' => $urls, 'lastmod' => $lastmod];
    }
}
