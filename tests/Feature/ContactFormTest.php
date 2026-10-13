<?php

declare(strict_types=1);

use App\Models\ContactMessage;
use App\Models\Profile;
use App\Notifications\NewContactMessage;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\RateLimiter;

beforeEach(function (): void {
    Notification::fake();
    RateLimiter::clear('min:127.0.0.1');
    Profile::factory()->create(['email' => 'owner@example.com']);
});

function validPayload(array $overrides = []): array
{
    return [
        'name' => '  Abebe   Kebede ',
        'email' => 'Abebe@Example.com',
        'inquiry_type' => 'web_system',
        'message' => 'We need a staff ID system for our bureau, can we talk?',
        'website' => '',
        ...$overrides,
    ];
}

it('stores the message, normalises input and notifies the owner', function (): void {
    $this->from('/')->post('/contact', validPayload())
        ->assertRedirect('/')
        ->assertSessionHas('success');

    $message = ContactMessage::sole();
    expect($message->name)->toBe('Abebe Kebede')
        ->and($message->email)->toBe('abebe@example.com')
        ->and($message->ip_address)->toBe('127.0.0.1')
        ->and($message->read_at)->toBeNull();

    Notification::assertSentTo(
        new AnonymousNotifiable,
        NewContactMessage::class,
        fn (NewContactMessage $n, array $channels, AnonymousNotifiable $notifiable) => $notifiable->routes['mail'] === 'owner@example.com'
            && $n->contactMessage->is($message),
    );
});

it('prefers the configured notification address', function (): void {
    config(['portfolio.notify_email' => 'inbox@example.com']);

    $this->post('/contact', validPayload());

    Notification::assertSentTo(new AnonymousNotifiable, NewContactMessage::class,
        fn ($n, $c, AnonymousNotifiable $notifiable) => $notifiable->routes['mail'] === 'inbox@example.com');
});

it('validates input', function (array $overrides, string $field): void {
    $this->post('/contact', validPayload($overrides))->assertSessionHasErrors($field);
    expect(ContactMessage::count())->toBe(0);
})->with([
    'missing name' => [['name' => ''], 'name'],
    'bad email' => [['email' => 'not-an-email'], 'email'],
    'short message' => [['message' => 'Hi'], 'message'],
    'unknown type' => [['inquiry_type' => 'hacking'], 'inquiry_type'],
    'huge message' => [['message' => str_repeat('a', 5001)], 'message'],
]);

it('silently drops honeypot submissions', function (): void {
    $this->post('/contact', validPayload(['website' => 'https://spam.example']))
        ->assertSessionHas('success');

    expect(ContactMessage::count())->toBe(0);
    Notification::assertNothingSent();
});

it('ignores attempts to mass-assign protected columns', function (): void {
    $this->post('/contact', validPayload(['read_at' => now()->toDateTimeString(), 'ip_address' => '1.2.3.4']));

    $message = ContactMessage::sole();
    expect($message->read_at)->toBeNull()->and($message->ip_address)->toBe('127.0.0.1');
});

it('rate limits submissions per minute', function (): void {
    config(['portfolio.contact.per_minute' => 2]);

    $this->post('/contact', validPayload())->assertSessionHasNoErrors();
    $this->post('/contact', validPayload())->assertSessionHasNoErrors();
    $this->post('/contact', validPayload())->assertSessionHasErrors('message');

    expect(ContactMessage::count())->toBe(2);
});
