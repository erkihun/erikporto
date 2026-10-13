<?php

declare(strict_types=1);

use App\Models\User;
use Filament\Auth\MultiFactor\App\AppAuthentication;
use Filament\Auth\Pages\Login;
use Filament\Facades\Filament;
use Livewire\Livewire;

beforeEach(function (): void {
    Filament::setCurrentPanel('admin');
});

it('sends an admin without an authenticator app to the set-up page first', function (string $uri): void {
    $this->actingAs(User::factory()->admin()->create())
        ->get($uri)
        ->assertRedirect('/admin/multi-factor-authentication/set-up');
})->with(['/admin', '/admin/projects', '/admin/profiles']);

it('opens the set-up page for an admin without an authenticator app', function (): void {
    $this->actingAs(User::factory()->admin()->create())
        ->get('/admin/multi-factor-authentication/set-up')
        ->assertOk();
});

it('lets an admin with an authenticator app into the panel and the profile page', function (): void {
    $admin = User::factory()->admin()->withMfa()->create();

    $this->actingAs($admin)->get('/admin')->assertOk();
    $this->actingAs($admin)->get('/admin/profile')->assertOk();
});

it('asks for the authenticator code after a correct password', function (): void {
    $admin = User::factory()->admin()->withMfa()->create(['email' => 'admin@example.com']);

    Livewire::test(Login::class)
        ->fillForm(['email' => 'admin@example.com', 'password' => 'password'])
        ->call('authenticate')
        ->assertHasNoFormErrors();

    // Password alone does not sign in.
    $this->assertGuest();
    expect($admin->getAppAuthenticationSecret())->not->toBeEmpty();
});

it('signs in with the password and a valid authenticator code', function (): void {
    $admin = User::factory()->admin()->withMfa()->create(['email' => 'admin@example.com']);
    $code = AppAuthentication::make()->getCurrentCode($admin);

    Livewire::test(Login::class)
        ->fillForm(['email' => 'admin@example.com', 'password' => 'password'])
        ->call('authenticate')
        ->set('data.multiFactor.app.code', $code)
        ->call('authenticate')
        ->assertHasNoErrors();

    $this->assertAuthenticatedAs($admin);
});

it('rejects a wrong authenticator code', function (): void {
    User::factory()->admin()->withMfa()->create(['email' => 'admin@example.com']);

    Livewire::test(Login::class)
        ->fillForm(['email' => 'admin@example.com', 'password' => 'password'])
        ->call('authenticate')
        ->set('data.multiFactor.app.code', '000000')
        ->call('authenticate')
        ->assertHasErrors(['data.multiFactor.app.code']);

    $this->assertGuest();
});

it('accepts a recovery code once', function (): void {
    $admin = User::factory()->admin()->withMfa()->create(['email' => 'admin@example.com']);

    Livewire::test(Login::class)
        ->fillForm(['email' => 'admin@example.com', 'password' => 'password'])
        ->call('authenticate')
        ->set('data.multiFactor.app.useRecoveryCode', true)
        ->set('data.multiFactor.app.recoveryCode', 'recovery-code-one')
        ->call('authenticate')
        ->assertHasNoErrors();

    $this->assertAuthenticatedAs($admin);
    expect($admin->fresh()->getAppAuthenticationRecoveryCodes())->toHaveCount(1);
});

it('throttles repeated failed logins', function (): void {
    User::factory()->admin()->withMfa()->create(['email' => 'admin@example.com']);

    $login = Livewire::test(Login::class);
    foreach (range(1, 5) as $attempt) {
        $login->fillForm(['email' => 'admin@example.com', 'password' => 'wrong'])
            ->call('authenticate')
            ->assertHasFormErrors(['email']);
    }

    // The sixth attempt is refused before the password is even checked.
    $login->fillForm(['email' => 'admin@example.com', 'password' => 'password'])
        ->call('authenticate')
        ->assertNotified();

    $this->assertGuest();
});

it('stores the authenticator secret and recovery codes encrypted and never serialises them', function (): void {
    $admin = User::factory()->admin()->withMfa()->create();
    $raw = DB::table('users')->where('id', $admin->id)->first();

    expect($raw->app_authentication_secret)->not->toBe($admin->getAppAuthenticationSecret())
        ->and($raw->app_authentication_recovery_codes)->not->toContain('$2y$')
        ->and($admin->toArray())->not->toHaveKeys(['app_authentication_secret', 'app_authentication_recovery_codes']);
});
