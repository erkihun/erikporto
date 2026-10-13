<?php

declare(strict_types=1);

use App\Filament\Resources\ContactMessages\Pages\ViewContactMessage;
use App\Filament\Resources\Projects\Pages\CreateProject;
use App\Models\ContactMessage;
use App\Models\Profile;
use App\Models\Project;
use App\Models\User;
use Livewire\Livewire;

it('sends guests to the login page', function (): void {
    $this->get('/admin')->assertRedirect('/admin/login');
});

it('forbids authenticated users who are not admins', function (): void {
    $this->actingAs(User::factory()->create())->get('/admin')->assertForbidden();
});

it('lets admins open every content screen', function (string $uri): void {
    $this->actingAs(User::factory()->admin()->create())->get($uri)->assertOk();
})->with([
    '/admin',
    '/admin/projects',
    '/admin/projects/create',
    '/admin/skill-groups',
    '/admin/experiences',
    '/admin/process-steps',
    '/admin/contact-messages',
]);

it('opens the profile editor directly', function (): void {
    $profile = Profile::factory()->create();

    $this->actingAs(User::factory()->admin()->create())
        ->get('/admin/profiles')
        ->assertRedirect("/admin/profiles/{$profile->id}/edit");
});

it('creates a project from the admin form', function (): void {
    $this->actingAs(User::factory()->admin()->create());

    Livewire::test(CreateProject::class)
        ->fillForm([
            'title' => 'Court Case Management',
            'slug' => 'court-ms',
            'category' => 'software',
            'summary' => 'Tracks cases.',
            'problem' => 'Manual files.',
            'role' => 'Developer.',
            'solution' => 'A web system.',
            'outcome' => 'Faster follow-up.',
            'stack' => ['Laravel'],
            'is_published' => true,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(Project::query()->where('slug', 'court-ms')->sole()->stack)->toBe(['Laravel']);
});

it('rejects a duplicate slug', function (): void {
    Project::factory()->create(['slug' => 'taken']);
    $this->actingAs(User::factory()->admin()->create());

    Livewire::test(CreateProject::class)
        ->fillForm(['title' => 'X', 'slug' => 'taken', 'category' => 'software', 'summary' => 's', 'problem' => 'p', 'role' => 'r', 'solution' => 's', 'outcome' => 'o', 'stack' => ['PHP']])
        ->call('create')
        ->assertHasFormErrors(['slug' => 'unique']);
});

it('marks a message as read when an admin opens it', function (): void {
    $message = ContactMessage::factory()->create();
    $this->actingAs(User::factory()->admin()->create());

    Livewire::test(ViewContactMessage::class, ['record' => $message->getRouteKey()])->assertOk();

    expect($message->fresh()->read_at)->not->toBeNull();
});
