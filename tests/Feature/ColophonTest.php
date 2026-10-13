<?php

declare(strict_types=1);

use App\Models\Profile;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function (): void {
    Profile::factory()->create();
    $this->dir = storage_path('framework/testing/colophon');
    @mkdir($this->dir, 0777, true);
    config([
        'portfolio.build_meta' => $this->dir.'/build-meta.json',
        'portfolio.quality_report' => $this->dir.'/quality.json',
        'portfolio.repo_url' => null,
    ]);
});

afterEach(function (): void {
    @unlink($this->dir.'/build-meta.json');
    @unlink($this->dir.'/quality.json');
});

it('states the measured build, the last CI run and the deployed commit', function (): void {
    file_put_contents($this->dir.'/build-meta.json', json_encode([
        'built_at' => '2026-10-14T08:00:00Z',
        'commit' => 'abcdef1234567890',
        'stack' => ['laravel' => '12.70.0', 'inertia' => '2.3.29', 'preact' => '10.28.0', 'filament' => '4.15.1', 'tailwindcss' => '4.1.0', 'vite' => '7.1.0'],
        'weight' => ['js' => 91136, 'css' => 9216, 'fonts' => 93594, 'fonts_amharic' => 152269],
    ]));
    file_put_contents($this->dir.'/quality.json', json_encode([
        'measured_at' => '2026-10-14T07:50:00Z',
        'tests' => ['total' => 136, 'passed' => 135, 'skipped' => 1, 'failed' => 0],
        'lighthouse' => ['pages' => 5, 'performance' => 97, 'accessibility' => 100, 'best_practices' => 100, 'seo' => 100],
    ]));
    config(['portfolio.repo_url' => 'https://github.com/example/portfolio/']);

    $this->get('/colophon')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Colophon')
            ->where('colophon.stack.0', ['name' => 'Laravel', 'version' => '12.70.0'])
            ->where('colophon.stack.1', ['name' => 'PHP', 'version' => PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION.'.'.PHP_RELEASE_VERSION])
            ->where('colophon.weight', ['js' => 91136, 'css' => 9216, 'fonts' => 93594, 'fonts_amharic' => 152269])
            ->where('colophon.build.commit', 'abcdef1')
            ->where('colophon.build.commit_url', 'https://github.com/example/portfolio/commit/abcdef1234567890')
            ->where('colophon.quality.tests', ['passed' => 135, 'total' => 136])
            ->where('colophon.quality.lighthouse.accessibility', 100)
            ->where('colophon.repository_url', 'https://github.com/example/portfolio'));
});

it('leaves out what was not measured instead of guessing', function (): void {
    $this->get('/colophon')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('colophon.weight', null)
            ->where('colophon.build', null)
            ->where('colophon.quality', null)
            ->where('colophon.repository_url', null)
            ->where('colophon.stack.0', ['name' => 'Laravel', 'version' => app()->version()])
            ->has('colophon.stack', 2));
});

it('ignores a broken quality report', function (): void {
    file_put_contents($this->dir.'/quality.json', '{not json');

    $this->get('/colophon')->assertOk()->assertInertia(fn (Assert $page) => $page->where('colophon.quality', null));
});

it('exists in both languages and in the sitemap', function (): void {
    $this->get('/am/colophon')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('locale', 'am')
            ->where('urls.colophon', '/am/colophon')
            ->where('locales.0.url', rtrim((string) config('app.url'), '/').'/colophon'));

    $this->get('/sitemap.xml')->assertSee('/colophon</loc>', false)->assertSee('/am/colophon</loc>', false);
});
