<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Enums\Locale;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

/**
 * Applies the route's locale (`->middleware('locale:am')`). Without a parameter
 * (the fallback route) the locale comes from the URL prefix, so a 404 under
 * /am is rendered in Amharic too.
 */
final class SetLocale
{
    public function handle(Request $request, Closure $next, ?string $locale = null): Response
    {
        $resolved = $locale !== null ? Locale::from($locale) : Locale::fromPath($request->path());

        App::setLocale($resolved->value);

        return $next($request);
    }
}
