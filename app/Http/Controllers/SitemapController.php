<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Project;
use Illuminate\Http\Response;

final class SitemapController extends Controller
{
    public function __invoke(): Response
    {
        $projects = Project::query()->published()->ordered()->get(['slug', 'updated_at']);

        return response()
            ->view('sitemap', ['projects' => $projects])
            ->header('Content-Type', 'application/xml');
    }
}
