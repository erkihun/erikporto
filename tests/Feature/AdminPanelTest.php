<?php

declare(strict_types=1);

use App\Enums\NoteStatus;
use App\Filament\Resources\ContactMessages\Pages\ViewContactMessage;
use App\Filament\Resources\Notes\Pages\CreateNote;
use App\Filament\Resources\NowUpdates\Pages\CreateNowUpdate;
use App\Filament\Resources\Profiles\Pages\EditProfile;
use App\Filament\Resources\Projects\Pages\CreateProject;
use App\Filament\Resources\Projects\Pages\EditProject;
use App\Models\ContactMessage;
use App\Models\Note;
use App\Models\NowUpdate;
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
    $this->actingAs(User::factory()->admin()->withMfa()->create())->get($uri)->assertOk();
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

    $this->actingAs(User::factory()->admin()->withMfa()->create())
        ->get('/admin/profiles')
        ->assertRedirect("/admin/profiles/{$profile->id}/edit");
});

it('creates a project from the admin form with English and Amharic copy', function (): void {
    $this->actingAs(User::factory()->admin()->withMfa()->create());

    Livewire::test(CreateProject::class)
        ->fillForm([
            'title.en' => 'Court Case Management',
            'title.am' => 'የፍርድ ቤት ጉዳዮች አስተዳደር',
            'slug' => 'court-ms',
            'category' => 'software',
            'summary.en' => 'Tracks cases.',
            'problem.en' => 'Manual files.',
            'role.en' => 'Developer.',
            'solution.en' => 'A web system.',
            'outcome.en' => 'Faster follow-up.',
            'stack' => ['Laravel'],
            'is_published' => true,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $project = Project::query()->where('slug', 'court-ms')->sole();
    expect($project->stack)->toBe(['Laravel'])
        ->and($project->getTranslations('title'))->toBe(['en' => 'Court Case Management', 'am' => 'የፍርድ ቤት ጉዳዮች አስተዳደር'])
        // Left blank in the Amharic tab: not stored, so /am falls back to English.
        ->and($project->getTranslations('summary'))->toBe(['en' => 'Tracks cases.']);
});

it('fills the slug from the English title', function (): void {
    $this->actingAs(User::factory()->admin()->withMfa()->create());

    Livewire::test(CreateProject::class)
        ->fillForm(['title.en' => 'Recruitment Portal'])
        ->assertSchemaStateSet(['slug' => 'recruitment-portal']);
});

it('requires the English copy but not the Amharic', function (): void {
    $this->actingAs(User::factory()->admin()->withMfa()->create());

    Livewire::test(CreateProject::class)
        ->fillForm(['title.am' => 'ፕሮጀክት', 'slug' => 'x', 'category' => 'software', 'stack' => ['PHP']])
        ->call('create')
        ->assertHasFormErrors(['title.en' => 'required', 'summary.en' => 'required'])
        ->assertHasNoFormErrors(['summary.am', 'problem.am']);
});

it('loads both languages into the project edit form', function (): void {
    $project = Project::factory()->create(['title' => ['en' => 'EUISIS', 'am' => 'ኢዩሲስ']]);
    $this->actingAs(User::factory()->admin()->withMfa()->create());

    Livewire::test(EditProject::class, ['record' => $project->getRouteKey()])
        ->assertSchemaStateSet(['title.en' => 'EUISIS', 'title.am' => 'ኢዩሲስ']);
});

it('saves the profile in both languages from the admin form', function (): void {
    $profile = Profile::factory()->create(['headline' => 'Developer', 'bio' => ['First paragraph.']]);
    $this->actingAs(User::factory()->admin()->withMfa()->create());

    Livewire::test(EditProfile::class, ['record' => $profile->getRouteKey()])
        ->assertSchemaStateSet(['headline.en' => 'Developer'])
        ->fillForm([
            'headline.am' => 'ገንቢ',
            'bio.am' => [['paragraph' => 'የመጀመሪያ አንቀጽ።']],
            'facts' => [['value' => '4', 'label' => ['en' => 'Projects', 'am' => 'ፕሮጀክቶች']]],
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $profile->refresh();
    expect($profile->getTranslations('headline'))->toBe(['en' => 'Developer', 'am' => 'ገንቢ'])
        ->and($profile->getTranslations('bio'))->toBe(['en' => ['First paragraph.'], 'am' => ['የመጀመሪያ አንቀጽ።']])
        ->and($profile->facts)->toBe([['value' => '4', 'label' => ['en' => 'Projects', 'am' => 'ፕሮጀክቶች']]]);
});

it('rejects a duplicate slug', function (): void {
    Project::factory()->create(['slug' => 'taken']);
    $this->actingAs(User::factory()->admin()->withMfa()->create());

    Livewire::test(CreateProject::class)
        ->fillForm(['title.en' => 'X', 'slug' => 'taken', 'category' => 'software', 'summary.en' => 's', 'problem.en' => 'p', 'role.en' => 'r', 'solution.en' => 's', 'outcome.en' => 'o', 'stack' => ['PHP']])
        ->call('create')
        ->assertHasFormErrors(['slug' => 'unique']);
});

it('marks a message as read when an admin opens it', function (): void {
    $message = ContactMessage::factory()->create();
    $this->actingAs(User::factory()->admin()->withMfa()->create());

    Livewire::test(ViewContactMessage::class, ['record' => $message->getRouteKey()])->assertOk();

    expect($message->fresh()->read_at)->not->toBeNull();
});

it('creates a scheduled note from the admin form', function (): void {
    $this->actingAs(User::factory()->admin()->withMfa()->create());

    Livewire::test(CreateNote::class)
        ->fillForm([
            'title' => 'Ethiopian dates in Laravel',
            'slug' => 'ethiopian-dates-in-laravel',
            'excerpt' => 'How I store and display E.C. dates.',
            'body' => "## Storage\n\nAlways store G.C.",
            'published_at' => now()->addDay()->format('Y-m-d H:i'),
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $note = Note::sole();
    expect($note->status)->toBe(NoteStatus::Scheduled)
        ->and($note->body_html)->toContain('id="storage"');
});

it('lets admins open the notes screens', function (): void {
    $this->actingAs(User::factory()->admin()->withMfa()->create());

    $this->get('/admin/notes')->assertOk();
    $this->get('/admin/notes/create')->assertOk();
    $this->get('/admin/notes/'.Note::factory()->create()->slug.'/edit')->assertOk();
});

it('creates a scheduled now update from the admin form', function (): void {
    $this->actingAs(User::factory()->admin()->withMfa()->create());

    Livewire::test(CreateNowUpdate::class)
        ->fillForm([
            'body' => 'Shipping the **notes** section.',
            'published_at' => now()->addDay()->format('Y-m-d H:i'),
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $update = NowUpdate::sole();
    expect($update->status)->toBe(NoteStatus::Scheduled)
        ->and($update->body_html)->toContain('<strong>notes</strong>');
});

it('lets admins open the now screens', function (): void {
    $this->actingAs(User::factory()->admin()->withMfa()->create());

    $this->get('/admin/now-updates')->assertOk();
    $this->get('/admin/now-updates/create')->assertOk();
    $this->get('/admin/now-updates/'.NowUpdate::factory()->create()->id.'/edit')->assertOk();
});
