<?php

declare(strict_types=1);

use App\Jobs\AnnounceOnTelegram;
use App\Models\Note;
use App\Models\Project;
use App\Services\Telegram\TelegramChannel;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;

function configureTelegram(): void
{
    config([
        'services.telegram.bot_token' => '123:abc',
        'services.telegram.channel_id' => '@erkihun_notes',
        'services.telegram.announce_notes' => true,
        'services.telegram.announce_projects' => false,
    ]);
}

it('posts a published note once, with escaped HTML and the note link', function (): void {
    configureTelegram();
    Http::fake(['api.telegram.org/*' => Http::response(['ok' => true])]);

    $note = Note::factory()->create(['slug' => 'dates', 'title' => 'Dates <& calendars>', 'excerpt' => 'Why E.C. matters']);

    Http::assertSentCount(1);
    Http::assertSent(fn ($request) => $request->url() === 'https://api.telegram.org/bot123:abc/sendMessage'
        && $request['chat_id'] === '@erkihun_notes'
        && $request['parse_mode'] === 'HTML'
        && str_contains($request['text'], '<b>Dates &lt;&amp; calendars&gt;</b>')
        && str_contains($request['text'], route('notes.show', $note)));

    expect($note->fresh()->telegram_posted_at)->not->toBeNull();
});

it('never posts the same note twice', function (): void {
    configureTelegram();
    Http::fake(['api.telegram.org/*' => Http::response(['ok' => true])]);

    $note = Note::factory()->create();
    (new AnnounceOnTelegram($note))->handle(app(TelegramChannel::class));
    $note->fresh()->update(['title' => 'Edited after publishing']);
    $this->artisan('portfolio:announce')->assertSuccessful();

    Http::assertSentCount(1);
});

it('does nothing when Telegram is not configured', function (): void {
    Http::fake();
    Queue::fake();

    Note::factory()->create();
    $this->artisan('portfolio:announce')->assertSuccessful();

    Queue::assertNothingPushed();
    Http::assertNothingSent();
});

it('does not announce drafts or scheduled notes until they go live', function (): void {
    configureTelegram();
    Queue::fake();

    Note::factory()->draft()->create();
    $scheduled = Note::factory()->scheduled('+1 hour')->create();
    $this->artisan('portfolio:announce');
    Queue::assertNothingPushed();

    $this->travel(61)->minutes();
    $this->artisan('portfolio:announce');

    Queue::assertPushed(AnnounceOnTelegram::class, 1);
    Queue::assertPushed(AnnounceOnTelegram::class, fn (AnnounceOnTelegram $job) => $job->record->is($scheduled));
});

it('leaves the note unmarked and rethrows when Telegram fails, so the job retries', function (): void {
    configureTelegram();
    Http::fake(['api.telegram.org/*' => Http::response(['ok' => false], 500)]);
    Queue::fake(); // create without auto-dispatch, then run the job by hand

    $note = Note::factory()->create();

    expect(fn () => (new AnnounceOnTelegram($note))->handle(app(TelegramChannel::class)))
        ->toThrow(RequestException::class);
    expect($note->fresh()->telegram_posted_at)->toBeNull();
});

it('skips a note that was unpublished before the job ran', function (): void {
    configureTelegram();
    Http::fake();
    Queue::fake();

    $note = Note::factory()->create();
    $note->update(['published_at' => null]);

    (new AnnounceOnTelegram($note))->handle(app(TelegramChannel::class));

    Http::assertNothingSent();
});

it('announces projects only when the project toggle is on', function (): void {
    configureTelegram();
    Queue::fake();

    Project::factory()->create();
    Queue::assertNothingPushed();

    config(['services.telegram.announce_projects' => true]);
    $project = Project::factory()->create();

    Queue::assertPushed(AnnounceOnTelegram::class, fn (AnnounceOnTelegram $job) => $job->record->is($project));
});
