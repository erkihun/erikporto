<?php

declare(strict_types=1);

use App\Models\Project;

it('lists the home page and published projects only', function (): void {
    Project::factory()->create(['slug' => 'public-one']);
    Project::factory()->draft()->create(['slug' => 'hidden-one']);

    $this->get('/sitemap.xml')
        ->assertOk()
        ->assertHeader('Content-Type', 'application/xml')
        ->assertSee('/work/public-one', false)
        ->assertDontSee('hidden-one', false);
});
