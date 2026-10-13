<?php

declare(strict_types=1);

use App\Models\Profile;
use Illuminate\Support\Facades\Vite;

beforeEach(function (): void {
    // Render as a production build even when a local Vite dev server is running.
    Vite::useHotFile(storage_path('framework/testing/no-vite-hot-file'));
    Profile::factory()->create();
});

it('sends a nonce-based Content Security Policy with public pages', function (): void {
    $response = $this->get('/')->assertOk();
    $policy = (string) $response->headers->get('Content-Security-Policy');
    $html = (string) $response->getContent();

    preg_match("/'nonce-([^']+)'/", $policy, $match);
    $nonce = $match[1] ?? '';

    expect($nonce)->not->toBe('')
        ->and($policy)->toContain("default-src 'self'")
        ->and($policy)->toContain("script-src 'self' 'nonce-{$nonce}'")
        ->and($policy)->toContain("style-src-elem 'self' 'nonce-{$nonce}'")
        ->and($policy)->toContain("font-src 'self'")
        ->and($policy)->toContain("object-src 'none'")
        ->and($policy)->toContain("frame-ancestors 'none'")
        ->and($policy)->not->toContain('unsafe-eval')
        // Every inline script and style element carries the nonce; none is left without it.
        ->and(preg_match_all('/<(script|style)(?![^>]*\bnonce="'.preg_quote($nonce, '/').'")[^>]*>/', $html))->toBe(0)
        ->and(preg_match_all('/<(script|style)\b/', $html))->toBeGreaterThan(1);
});

it('uses a fresh nonce for every response', function (): void {
    $first = (string) $this->get('/')->headers->get('Content-Security-Policy');
    $second = (string) $this->get('/')->headers->get('Content-Security-Policy');

    expect($first)->not->toBe($second);
});

it('sends the baseline headers on public pages, feeds and errors', function (string $uri, int $status): void {
    $this->get($uri)
        ->assertStatus($status)
        ->assertHeader('X-Content-Type-Options', 'nosniff')
        ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
        ->assertHeader('X-Frame-Options', 'DENY')
        ->assertHeader('Cross-Origin-Opener-Policy', 'same-origin')
        ->assertHeader('Permissions-Policy');
})->with([['/', 200], ['/sitemap.xml', 200], ['/no-such-page', 404]]);

it('keeps the policy off non-HTML responses', function (): void {
    $this->get('/sitemap.xml')->assertHeaderMissing('Content-Security-Policy');
    $this->getJson('/palette.json')->assertHeaderMissing('Content-Security-Policy');
});

it('gives the admin panel the baseline headers without a CSP', function (): void {
    $this->get('/admin/login')
        ->assertOk()
        ->assertHeader('X-Content-Type-Options', 'nosniff')
        ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
        ->assertHeaderMissing('Content-Security-Policy');
});

it('sends HSTS only in production over HTTPS', function (): void {
    $this->get('/')->assertHeaderMissing('Strict-Transport-Security');

    $this->app['env'] = 'production';

    $this->get('https://localhost/')
        ->assertHeader('Strict-Transport-Security', 'max-age=31536000; includeSubDomains')
        ->assertHeader('Content-Security-Policy');
    expect((string) $this->get('https://localhost/')->headers->get('Content-Security-Policy'))->toContain('upgrade-insecure-requests');
});
