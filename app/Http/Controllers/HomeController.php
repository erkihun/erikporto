<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Queries\HomePageQuery;
use Inertia\Inertia;
use Inertia\Response;

final class HomeController extends Controller
{
    public function __invoke(HomePageQuery $query): Response
    {
        $props = $query->get();

        // A fresh production install has no profile until the owner fills it in /admin.
        if ($props === null) {
            return Inertia::render('ComingSoon');
        }

        return Inertia::render('Home', $props);
    }
}
