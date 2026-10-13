<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Vite;
use Symfony\Component\HttpFoundation\Response;

/**
 * Response headers that harden the site.
 *
 * - Every response: no MIME sniffing, a strict referrer policy, no powerful browser
 *   features, an isolated browsing context, and HSTS in production over HTTPS.
 * - Public HTML (`security:public`, the web group): a Content Security Policy with a
 *   per-request nonce. Scripts and stylesheets run only from this origin or with that
 *   nonce (the theme script, the inlined stylesheet and Vite's tags carry it); fonts,
 *   images and requests stay on this origin; nothing may frame the site.
 * - The admin panel (`security:admin`): the same baseline without a CSP, because
 *   Livewire and Alpine rely on inline scripts and evaluated expressions. It may only be
 *   framed by itself.
 */
final class SecurityHeaders
{
    public function handle(Request $request, Closure $next, string $profile = 'public'): Response
    {
        $public = $profile === 'public';
        if ($public) {
            Vite::useCspNonce();
        }

        $response = $next($request);
        $headers = $response->headers;

        $headers->set('X-Content-Type-Options', 'nosniff');
        $headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), payment=(), usb=(), browsing-topics=()');
        $headers->set('Cross-Origin-Opener-Policy', 'same-origin');
        $headers->set('X-Frame-Options', $public ? 'DENY' : 'SAMEORIGIN');

        if (app()->isProduction() && $request->isSecure()) {
            $headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        // The Vite dev server injects its own client and websocket; the policy is for builds.
        if ($public && ! Vite::isRunningHot() && str_starts_with((string) $headers->get('Content-Type'), 'text/html')) {
            $headers->set('Content-Security-Policy', self::policy((string) Vite::cspNonce()));
        }

        return $response;
    }

    public static function policy(string $nonce): string
    {
        $directives = [
            "default-src 'self'",
            "script-src 'self' 'nonce-{$nonce}'",
            "style-src-elem 'self' 'nonce-{$nonce}'",
            // Inline style attributes come from server-rendered components (no script can run from them).
            "style-src-attr 'unsafe-inline'",
            "img-src 'self' data:",
            "font-src 'self'",
            "connect-src 'self'",
            "object-src 'none'",
            "base-uri 'self'",
            "form-action 'self'",
            "frame-ancestors 'none'",
            "manifest-src 'self'",
        ];

        if (app()->isProduction()) {
            $directives[] = 'upgrade-insecure-requests';
        }

        return implode('; ', $directives);
    }
}
