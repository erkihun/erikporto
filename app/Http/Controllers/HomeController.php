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

        abort_if($props === null, 503, 'The portfolio profile has not been set up yet.');

        return Inertia::render('Home', $props);
    }
}
