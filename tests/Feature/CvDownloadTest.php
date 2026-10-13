<?php

declare(strict_types=1);

use App\Models\Profile;
use Illuminate\Support\Facades\Storage;

it('returns 404 when no CV is uploaded', function (): void {
    Profile::factory()->create(['cv_path' => null]);

    $this->get('/cv')->assertNotFound();
});

it('returns 404 when the stored file is missing', function (): void {
    Storage::fake('local');
    Profile::factory()->create(['cv_path' => 'cv/gone.pdf']);

    $this->get('/cv')->assertNotFound();
});

it('downloads the CV from private storage with a clean filename', function (): void {
    Storage::fake('local');
    Storage::disk('local')->put('cv/upload-123.pdf', '%PDF-1.7 fake');
    Profile::factory()->create(['name' => 'Erkihun Bewket', 'cv_path' => 'cv/upload-123.pdf']);

    $this->get('/cv')
        ->assertOk()
        ->assertDownload('erkihun-bewket-cv.pdf');
});
