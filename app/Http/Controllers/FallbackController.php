<?php

declare(strict_types=1);

namespace App\Http\Controllers;

/**
 * Unknown URLs run through the web middleware (session, locale, shared props) before
 * the 404, so the error page knows the visitor's language and theme.
 */
final class FallbackController
{
    public function __invoke(): never
    {
        abort(404);
    }
}
