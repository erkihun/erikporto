<?php

declare(strict_types=1);

use App\Models\Experience;
use App\Models\Profile;
use App\Models\Project;
use App\Models\SkillGroup;
use Inertia\Testing\AssertableInertia as Assert;

it('renders the home page from a profile alone, with empty sections left out of the navigation', function (): void {
    Profile::factory()->create([
        'bio' => [],
        'facts' => null,
        'social_links' => null,
        'about_statement' => null,
        'location' => null,
        'email' => null,
        'avatar_path' => null,
        'cv_path' => null,
    ]);

    $this->get('/')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Home')
            ->where('projects', [])
            ->where('capabilities', [])
            ->where('process', [])
            ->where('experience', [])
            ->where('latestNotes', [])
            ->where('profile.bio', [])
            ->where('profile.facts', [])
            ->where('profile.social_links', [])
            ->where('profile.avatar', null)
            ->where('profile.cv_url', null)
            ->where('profile.email', null)
            // Work stays (it shows the "case studies in preparation" state); empty sections go.
            ->where('homeSections', ['work', 'about', 'contact'])
            ->where('hasNotes', false)
            ->where('hasNow', false)
            ->where('translations', fn ($t): bool => filled($t['work.empty_intro']) && filled($t['work.empty_status'])));
});

it('links a section from the navigation as soon as it has content', function (): void {
    Profile::factory()->create();
    $this->get('/')->assertInertia(fn (Assert $page) => $page->where('homeSections', ['work', 'about', 'contact']));

    SkillGroup::factory()->create();
    Experience::factory()->create();

    $this->get('/')->assertInertia(fn (Assert $page) => $page->where('homeSections', ['work', 'capabilities', 'experience', 'about', 'contact']));
});

it('uses the same navigation on other pages', function (): void {
    Profile::factory()->create();
    Project::factory()->create(['slug' => 'only-one']);

    $this->get('/work/only-one')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('homeSections', ['work', 'about', 'contact'])
            ->where('next', null)
            ->where('position.total', 1));
});
