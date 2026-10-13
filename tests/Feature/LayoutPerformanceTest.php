<?php

declare(strict_types=1);

use App\Models\Profile;
use Illuminate\Support\Facades\Vite;

beforeEach(function (): void {
    // Render as a production build even when a local Vite dev server is running.
    Vite::useHotFile(storage_path('framework/testing/no-vite-hot-file'));
    Profile::factory()->create();
});

it('inlines the stylesheet instead of linking it', function (): void {
    $html = $this->get('/')->assertOk()->getContent();

    expect($html)->toContain('<style>')
        ->and($html)->not->toMatch('/<link[^>]+rel="stylesheet"/');
});

it('preloads the self-hosted display face and loads nothing from Google Fonts', function (): void {
    $html = $this->get('/')->assertOk()->getContent();

    expect($html)->toMatch('/<link rel="preload" href="[^"]+archivo-latin-critical-[^"]+\.woff2" as="font" type="font\/woff2" crossorigin>/')
        ->and($html)->not->toContain('fonts.googleapis.com')
        ->and($html)->not->toContain('fonts.gstatic.com');
});
