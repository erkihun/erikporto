<?php

declare(strict_types=1);

use App\Http\Middleware\HandleInertiaRequests;
use App\Models\Profile;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Support\Facades\Route;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function (): void {
    Profile::factory()->create(['name' => 'Erkihun Bewket']);

    Route::middleware('web')->group(function (): void {
        Route::get('/_test/forbidden', fn () => abort(403));
        Route::get('/_test/too-many', fn () => abort(429));
        Route::get('/_test/broken', fn () => throw new RuntimeException('Boom'));
        Route::post('/_test/expired', fn () => throw new TokenMismatchException);
    });
});

it('renders unknown URLs as the designed 404 page', function (): void {
    $this->get('/no-such-page')
        ->assertNotFound()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Error')
            ->where('status', 404)
            ->where('owner.name', 'Erkihun Bewket')
            ->where('locale', 'en'));
});

it('answers in Amharic under /am', function (): void {
    $this->get('/am/no-such-page')
        ->assertNotFound()
        ->assertSee('<html lang="am">', false)
        ->assertInertia(fn (Assert $page) => $page
            ->component('Error')
            ->where('locale', 'am')
            ->where('urls.home', '/am')
            ->where('translations', fn ($t): bool => $t['errors.404.title'] === __('errors.404.title', locale: 'am')));
});

it('renders a missing case study as the 404 page', function (): void {
    $this->get('/work/missing')->assertNotFound()->assertInertia(fn (Assert $page) => $page->component('Error')->where('status', 404));
    $this->get('/notes/missing')->assertNotFound()->assertInertia(fn (Assert $page) => $page->component('Error'));
});

it('renders 403 and 429 as designed pages', function (string $uri, int $status): void {
    $this->get($uri)->assertStatus($status)->assertInertia(fn (Assert $page) => $page->component('Error')->where('status', $status));
})->with([['/_test/forbidden', 403], ['/_test/too-many', 429]]);

it('renders server errors as a calm page when debug is off', function (): void {
    config(['app.debug' => false]);

    $this->get('/_test/broken')
        ->assertStatus(500)
        ->assertInertia(fn (Assert $page) => $page->component('Error')->where('status', 500));
});

it('keeps the debug page for server errors while debugging', function (): void {
    config(['app.debug' => true]);

    $response = $this->get('/_test/broken')->assertStatus(500);

    expect($response->headers->get('X-Inertia'))->toBeNull()
        ->and((string) $response->getContent())->not->toContain('&quot;component&quot;:&quot;Error&quot;');
});

it('still renders the error page when the database is down', function (): void {
    // Every model query goes to a database that does not exist; the test's own connection stays intact.
    config([
        'app.debug' => false,
        'database.connections.down' => ['driver' => 'sqlite', 'database' => storage_path('framework/testing/missing/none.sqlite')],
        'database.default' => 'down',
    ]);

    try {
        $this->get('/_test/broken')
            ->assertStatus(500)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Error')
                ->where('owner.name', config('app.name'))
                ->where('hasNotes', false)
                ->where('homeSections', ['work', 'about', 'contact']));
    } finally {
        config(['database.default' => 'sqlite']);
    }
});

it('shows the maintenance page during deploys', function (): void {
    $this->app->maintenanceMode()->activate([]);

    try {
        $this->get('/')
            ->assertStatus(503)
            ->assertInertia(fn (Assert $page) => $page->component('Error')->where('status', 503));
    } finally {
        $this->app->maintenanceMode()->deactivate();
    }
});

it('sends an expired form back with a message instead of an error page', function (): void {
    $this->from('/am')
        ->post('/_test/expired')
        ->assertRedirect('/am')
        ->assertSessionHas('error', __('errors.page_expired'));
});

it('leaves admin and JSON errors to Laravel and Filament', function (): void {
    $admin = $this->get('/admin/no-such-page')->assertNotFound();
    expect((string) $admin->getContent())->not->toContain('&quot;component&quot;:&quot;Error&quot;');

    $this->getJson('/no-such-page')->assertNotFound()->assertJsonStructure(['message']);
});

it('keeps Inertia visits inside the app on errors', function (): void {
    $this->get('/no-such-page', ['X-Inertia' => 'true', 'X-Inertia-Version' => app(HandleInertiaRequests::class)->version(request())])
        ->assertNotFound()
        ->assertHeader('X-Inertia', 'true')
        ->assertJsonPath('component', 'Error');
});
