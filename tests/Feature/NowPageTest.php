<?php

declare(strict_types=1);

use App\Models\NowUpdate;
use App\Models\Profile;
use Illuminate\Database\Eloquent\MassAssignmentException;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(fn () => Profile::factory()->create(['name' => 'Erkihun Bewket']));

it('returns 404 until an update is published', function (): void {
    NowUpdate::factory()->draft()->create();
    NowUpdate::factory()->scheduled()->create();

    $this->get('/now')->assertNotFound();
});

it('shows the latest published update with older ones as history', function (): void {
    $older = NowUpdate::factory()->create(['body' => 'Older focus: the *court* system.', 'published_at' => now()->subMonths(2)]);
    $latest = NowUpdate::factory()->create(['body' => "## Building\n\nThe **notes** feature.", 'published_at' => now()->subDay()]);
    NowUpdate::factory()->scheduled()->create(['body' => 'Not yet']);
    NowUpdate::factory()->draft()->create(['body' => 'Draft']);

    $this->get('/now')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Now')
            ->where('current.id', $latest->id)
            ->where('current.body_html', fn (string $html): bool => str_contains($html, '<strong>notes</strong>'))
            ->has('previous', 1)
            ->where('previous.0.id', $older->id)
            ->where('previous.0.summary', 'Older focus: the court system.')
            ->where('owner.name', 'Erkihun Bewket'));
});

it('switches to a scheduled update on time even though the page is cached', function (): void {
    $current = NowUpdate::factory()->create(['published_at' => now()->subWeek()]);
    $next = NowUpdate::factory()->scheduled('+1 hour')->create();

    $this->get('/now')->assertInertia(fn (Assert $page) => $page->where('current.id', $current->id)->has('previous', 0));

    $this->travel(61)->minutes();

    $this->get('/now')->assertInertia(fn (Assert $page) => $page
        ->where('current.id', $next->id)
        ->where('previous.0.id', $current->id));
});

it('shares hasNow so the footer link appears only with a published update', function (): void {
    NowUpdate::factory()->draft()->create();
    $this->get('/')->assertInertia(fn (Assert $page) => $page->where('hasNow', false));

    NowUpdate::factory()->create();
    $this->get('/')->assertInertia(fn (Assert $page) => $page->where('hasNow', true));
});

it('renders Markdown through the shared safe renderer', function (): void {
    $update = NowUpdate::factory()->create(['body' => "Hi <script>alert(1)</script>\n\n[x](javascript:alert(1))"]);

    expect($update->body_html)
        ->not->toContain('<script')
        ->toContain('&lt;script&gt;')
        ->and(strtolower($update->body_html))->not->toContain('javascript:');
});

it('never accepts body_html as input', function (): void {
    $update = NowUpdate::factory()->create();

    expect(fn () => $update->update(['body_html' => '<b>injected</b>']))->toThrow(MassAssignmentException::class);
});

it('lists /now in the sitemap with lastmod only when an update is live', function (): void {
    NowUpdate::factory()->draft()->create();
    $this->get('/sitemap.xml')->assertDontSee('/now</loc>', false);

    NowUpdate::factory()->create();
    $this->get('/sitemap.xml')->assertSee('/now</loc><lastmod>', false);
});
