<?php

declare(strict_types=1);

use App\Enums\NoteStatus;
use App\Models\Note;
use App\Models\Profile;
use Illuminate\Database\Eloquent\MassAssignmentException;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(fn () => Profile::factory()->create(['name' => 'Erkihun Bewket']));

it('derives status from the publish date', function (): void {
    expect(Note::factory()->draft()->create()->status)->toBe(NoteStatus::Draft)
        ->and(Note::factory()->scheduled()->create()->status)->toBe(NoteStatus::Scheduled)
        ->and(Note::factory()->create()->status)->toBe(NoteStatus::Published);
});

it('renders the body, toc and reading time on save and never accepts them as input', function (): void {
    $note = Note::factory()->create(['body' => "## First\n\nHello"]);

    expect($note->body_html)->toContain('<h2 id="first">First</h2>')
        ->and($note->toc)->toBe([['id' => 'first', 'text' => 'First', 'level' => 2]])
        ->and($note->reading_minutes)->toBe(1);

    // Derived columns are not fillable; strict mode rejects the attempt outright.
    expect(fn () => $note->update(['body_html' => '<script>pwned</script>']))
        ->toThrow(MassAssignmentException::class);
    expect($note->fresh()->body_html)->not->toContain('pwned');

    $note->update(['body' => "## Second\n\nChanged"]);
    expect($note->fresh()->body_html)->toContain('id="second"');
});

it('lists only published notes, newest first', function (): void {
    Note::factory()->create(['slug' => 'older', 'published_at' => now()->subDays(3)]);
    Note::factory()->create(['slug' => 'newer', 'published_at' => now()->subDay()]);
    Note::factory()->draft()->create(['slug' => 'draft']);
    Note::factory()->scheduled()->create(['slug' => 'future']);

    $this->get('/notes')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Notes/Index')
            ->has('notes', 2)
            ->where('notes.0.slug', 'newer')
            ->where('notes.1.slug', 'older')
            ->where('notes.0.url', '/notes/newer')
            ->missing('notes.0.body_html')
            ->where('pagination.next', null));
});

it('paginates and 404s past the last page', function (): void {
    Note::factory()->count(13)->sequence(fn ($s) => ['published_at' => now()->subDays($s->index + 1)])->create();

    $this->get('/notes')->assertInertia(fn (Assert $page) => $page->has('notes', 12)->where('pagination.next', '/notes?page=2'));
    $this->get('/notes?page=2')->assertInertia(fn (Assert $page) => $page->has('notes', 1)->where('pagination.prev', '/notes?page=1'));
    $this->get('/notes?page=3')->assertNotFound();
});

it('shows a published note with its neighbours', function (): void {
    Note::factory()->create(['slug' => 'a', 'published_at' => now()->subDays(3)]);
    $middle = Note::factory()->create(['slug' => 'b', 'published_at' => now()->subDays(2), 'body' => "## Part one\n\nx\n\n## Part two\n\ny"]);
    Note::factory()->create(['slug' => 'c', 'published_at' => now()->subDay()]);

    $this->get('/notes/b')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Notes/Show')
            ->where('note.title', $middle->title)
            ->has('note.toc', 2)
            ->where('older.slug', 'a')
            ->where('newer.slug', 'c')
            ->where('owner.name', 'Erkihun Bewket'));
});

it('hides drafts and scheduled notes', function (): void {
    Note::factory()->draft()->create(['slug' => 'draft']);
    Note::factory()->scheduled()->create(['slug' => 'future']);

    $this->get('/notes/draft')->assertNotFound();
    $this->get('/notes/future')->assertNotFound();
});

it('publishes a scheduled note on time even though pages are cached', function (): void {
    Note::factory()->scheduled('+1 hour')->create(['slug' => 'soon']);

    $this->get('/')->assertInertia(fn (Assert $page) => $page->has('latestNotes', 0)->where('hasNotes', false));
    $this->get('/notes/soon')->assertNotFound();

    $this->travel(61)->minutes();

    $this->get('/')->assertInertia(fn (Assert $page) => $page->has('latestNotes', 1)->where('hasNotes', true));
    $this->get('/notes/soon')->assertOk();
});

it('shows the three latest notes on the home page', function (): void {
    Note::factory()->count(4)->create();

    $this->get('/')->assertInertia(fn (Assert $page) => $page->has('latestNotes', 3));
});

it('serves an Atom feed of published notes only', function (): void {
    Note::factory()->create(['slug' => 'live-note', 'title' => 'Ethiopian dates in Laravel', 'body' => '**bold** <script>x</script>']);
    Note::factory()->draft()->create(['slug' => 'hidden-note']);

    $response = $this->get('/feed.xml')
        ->assertOk()
        ->assertHeader('Content-Type', 'application/atom+xml; charset=UTF-8');

    $xml = simplexml_load_string($response->getContent());
    expect($xml)->not->toBeFalse()
        ->and(count($xml->entry))->toBe(1)
        ->and((string) $xml->entry[0]->title)->toBe('Ethiopian dates in Laravel')
        ->and((string) $xml->entry[0]->content)->toContain('<strong>bold</strong>')
        ->and((string) $xml->entry[0]->content)->not->toContain('<script>');
});

it('adds published notes to the sitemap', function (): void {
    Note::factory()->create(['slug' => 'in-sitemap']);
    Note::factory()->draft()->create(['slug' => 'not-in-sitemap']);

    $this->get('/sitemap.xml')
        ->assertSee('/notes/in-sitemap', false)
        ->assertSee('/notes</loc>', false)
        ->assertDontSee('not-in-sitemap', false);
});
