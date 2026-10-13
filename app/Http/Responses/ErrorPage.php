<?php

declare(strict_types=1);

namespace App\Http\Responses;

use App\Enums\Locale;
use App\Http\Middleware\HandleInertiaRequests;
use App\Queries\ErrorPageQuery;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Public errors as designed Inertia pages (resources/js/pages/Error.tsx), registered in
 * bootstrap/app.php. The admin panel, Livewire and JSON clients keep Laravel's responses;
 * 500 keeps the debug page while APP_DEBUG is on. An expired form (419) goes back to the
 * form with a message instead of an error page.
 */
final class ErrorPage
{
    /** @var list<int> */
    public const STATUSES = [403, 404, 429, 500, 503];

    public function __invoke(Response $response, Throwable $exception, Request $request): Response
    {
        $status = $response->getStatusCode();

        if ($request->is('admin', 'admin/*', 'livewire/*', 'up') || ($request->expectsJson() && ! $request->header('X-Inertia'))) {
            return $response;
        }

        if ($status === 419 && $request->hasSession()) {
            return back()->with('error', __('errors.page_expired'));
        }

        if (! in_array($status, self::STATUSES, true) || ($status === 500 && config('app.debug'))) {
            return $response;
        }

        // Errors raised before the web middleware ran (maintenance mode, a database that is
        // down) have no locale or shared props yet; both are set here from the request alone.
        App::setLocale(Locale::fromPath($request->path())->value);
        Inertia::share(app(HandleInertiaRequests::class)->share($request));

        return Inertia::render('Error', ErrorPageQuery::props($status))
            ->toResponse($request)
            ->setStatusCode($status);
    }
}
