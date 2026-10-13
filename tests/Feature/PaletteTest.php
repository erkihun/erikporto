<?php

declare(strict_types=1);

use App\Models\Note;
use App\Models\Profile;
use App\Models\Project;

it('lists published case studies and notes for the command palette', function (): void {
    Project::factory()->create(['slug' => 'euisis', 'title' => ['en' => 'EUISIS', 'am' => 'ኢዩሲስ'], 'sort_order' => 1]);
    Project::factory()->draft()->create(['slug' => 'secret']);
    Note::factory()->create(['slug' => 'dates', 'title' => 'Storing dates', 'published_at' => now()->subDay()]);
    Note::factory()->create(['slug' => 'draft', 'published_at' => null]);

    $this->getJson('/palette.json')
        ->assertOk()
        ->assertHeader('Cache-Control', 'max-age=300, private')
        ->assertExactJson([
            'projects' => [['title' => 'EUISIS', 'kind' => Project::query()->where('slug', 'euisis')->sole()->category->kind(), 'url' => '/work/euisis']],
            'notes' => [['title' => 'Storing dates', 'url' => '/notes/dates']],
        ]);

    $this->getJson('/am/palette.json')
        ->assertOk()
        ->assertJsonPath('projects.0.title', 'ኢዩሲስ')
        ->assertJsonPath('projects.0.url', '/am/work/euisis');
});

it('shares the palette URL for the current language', function (): void {
    Profile::factory()->create();

    $this->get('/am')->assertInertia(fn ($page) => $page->where('urls.palette', '/am/palette.json'));
});
