<?php

declare(strict_types=1);

use App\Filament\Resources\Projects\Pages\EditProject;
use App\Models\ImageAsset;
use App\Models\Note;
use App\Models\Profile;
use App\Models\Project;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Livewire\Livewire;

beforeEach(function (): void {
    Storage::fake('public');
});

/**
 * A JPEG with an EXIF block naming the camera ("SecretCam") and, optionally, an orientation.
 */
function jpegWithExif(int $width, int $height, int $orientation = 1): string
{
    $gd = imagecreatetruecolor($width, $height);
    imagefilledrectangle($gd, 0, 0, intdiv($width, 2), $height, (int) imagecolorallocate($gd, 39, 71, 245));
    ob_start();
    imagejpeg($gd, null, 90);
    $jpeg = (string) ob_get_clean();

    // TIFF header + one IFD with Make (ASCII) and Orientation (SHORT).
    $make = "SecretCam\0";
    $ifdOffset = 8;
    $dataOffset = $ifdOffset + 2 + 2 * 12 + 4;
    $tiff = 'II'.pack('v', 42).pack('V', $ifdOffset)
        .pack('v', 2)
        .pack('vvVV', 0x010F, 2, strlen($make), $dataOffset)
        .pack('vvVvv', 0x0112, 3, 1, $orientation, 0)
        .pack('V', 0)
        .$make;
    $payload = "Exif\0\0".$tiff;

    return "\xFF\xD8\xFF\xE1".pack('n', strlen($payload) + 2).$payload.substr($jpeg, 2);
}

function png(int $width, int $height): string
{
    $gd = imagecreatetruecolor($width, $height);
    ob_start();
    imagepng($gd);

    return (string) ob_get_clean();
}

it('strips EXIF from an uploaded cover and writes WebP variants', function (): void {
    Storage::disk('public')->put('projects/covers/a.jpg', jpegWithExif(2400, 1500));
    expect(Storage::disk('public')->get('projects/covers/a.jpg'))->toContain('SecretCam');

    Project::factory()->create(['cover_path' => 'projects/covers/a.jpg']);

    $asset = ImageAsset::query()->where('path', 'projects/covers/a.jpg')->sole();
    expect($asset->processed_at)->not->toBeNull()
        ->and([$asset->width, $asset->height])->toBe([2400, 1500])
        ->and(array_column($asset->variants, 'width'))->toBe([640, 1280, 1920])
        ->and(array_column($asset->variants, 'height'))->toBe([400, 800, 1200]);

    $original = (string) Storage::disk('public')->get('projects/covers/a.jpg');
    expect($original)->not->toContain('SecretCam')->not->toContain("Exif\0\0");

    foreach ($asset->variants as $variant) {
        $file = (string) Storage::disk('public')->get($variant['path']);
        expect(substr($file, 0, 4))->toBe('RIFF')->and(substr($file, 8, 4))->toBe('WEBP');
    }
});

it('sends srcset and intrinsic size with every image prop', function (): void {
    Storage::disk('public')->put('projects/covers/a.jpg', jpegWithExif(1600, 1000));
    Storage::disk('public')->put('profile/me.png', png(800, 1000));
    Profile::factory()->create(['avatar_path' => 'profile/me.png']);
    Project::factory()->create(['slug' => 'euisis', 'cover_path' => 'projects/covers/a.jpg']);

    $this->get('/')->assertInertia(fn (Assert $page) => $page
        ->where('projects.0.cover.width', 1600)
        ->where('projects.0.cover.height', 1000)
        ->where('projects.0.cover.srcset', fn (string $srcset): bool => str_contains($srcset, 'a-640.webp 640w') && str_contains($srcset, 'a-1280.webp 1280w'))
        ->where('profile.avatar.width', 800)
        ->where('profile.avatar.srcset', fn (string $srcset): bool => str_contains($srcset, 'me-640.webp 640w')));
});

it('keeps a small image as a single WebP at its own width', function (): void {
    Storage::disk('public')->put('projects/gallery/small.png', png(500, 300));

    Project::factory()->create(['gallery' => ['projects/gallery/small.png']]);

    expect(ImageAsset::query()->sole()->variants)->toBe([
        ['width' => 500, 'height' => 300, 'path' => 'projects/gallery/variants/small-500.webp'],
    ]);
});

it('turns photos upright using their EXIF orientation', function (): void {
    Storage::disk('public')->put('projects/covers/phone.jpg', jpegWithExif(300, 200, orientation: 6));

    Project::factory()->create(['cover_path' => 'projects/covers/phone.jpg']);

    $asset = ImageAsset::query()->sole();
    expect([$asset->width, $asset->height])->toBe([200, 300]);
    [$width, $height] = getimagesizefromstring((string) Storage::disk('public')->get('projects/covers/phone.jpg'));
    expect([$width, $height])->toBe([200, 300]);
})->skip(! function_exists('exif_read_data'), 'Needs the exif extension.');

it('deletes the old image and its variants when the cover is replaced', function (): void {
    $disk = Storage::disk('public');
    $disk->put('projects/covers/old.jpg', jpegWithExif(1400, 900));
    $disk->put('projects/covers/new.jpg', jpegWithExif(1400, 900));
    $project = Project::factory()->create(['cover_path' => 'projects/covers/old.jpg']);
    $oldFiles = ImageAsset::query()->where('path', 'projects/covers/old.jpg')->sole()->files();

    $project->update(['cover_path' => 'projects/covers/new.jpg']);

    foreach ($oldFiles as $file) {
        $disk->assertMissing($file);
    }
    expect(ImageAsset::query()->pluck('path')->all())->toBe(['projects/covers/new.jpg']);
});

it('deletes gallery images removed from the list and everything when the project is deleted', function (): void {
    $disk = Storage::disk('public');
    $disk->put('projects/gallery/one.png', png(700, 400));
    $disk->put('projects/gallery/two.png', png(700, 400));
    $project = Project::factory()->create(['gallery' => ['projects/gallery/one.png', 'projects/gallery/two.png']]);

    $project->update(['gallery' => ['projects/gallery/two.png']]);
    $disk->assertMissing(['projects/gallery/one.png', 'projects/gallery/variants/one-640.webp']);
    $disk->assertExists(['projects/gallery/two.png', 'projects/gallery/variants/two-640.webp']);

    $project->delete();
    $disk->assertMissing(['projects/gallery/two.png', 'projects/gallery/variants/two-640.webp']);
    expect(ImageAsset::query()->count())->toBe(0);
});

it('keeps an image that another record still uses', function (): void {
    $disk = Storage::disk('public');
    $disk->put('notes/attachments/shared.png', png(900, 500));
    $markdown = '![Diagram]('.$disk->url('notes/attachments/shared.png').')';
    $first = Note::factory()->create(['body' => $markdown]);
    Note::factory()->create(['body' => $markdown]);

    $first->update(['body' => 'No image any more.']);

    $disk->assertExists('notes/attachments/shared.png');
});

it('renders uploaded Markdown images with their size, srcset and lazy loading', function (): void {
    $disk = Storage::disk('public');
    $disk->put('notes/attachments/diagram.png', png(1500, 900));

    $note = Note::factory()->create(['body' => "Intro.\n\n![Diagram](".$disk->url('notes/attachments/diagram.png').')']);

    expect($note->fresh()->body_html)
        ->toContain('width="1500"')
        ->toContain('height="900"')
        ->toContain('loading="lazy"')
        ->toContain('decoding="async"')
        ->toContain('diagram-640.webp 640w')
        ->toContain('sizes="(min-width: 760px) 720px, 100vw"');
});

it('refuses SVG uploads in the admin', function (): void {
    $project = Project::factory()->create();
    $this->actingAs(User::factory()->admin()->withMfa()->create());

    Livewire::test(EditProject::class, ['record' => $project->getRouteKey()])
        ->fillForm(['cover_path' => UploadedFile::fake()->create('logo.svg', 4, 'image/svg+xml')])
        ->call('save')
        ->assertHasFormErrors(['cover_path']);
});

it('refuses images over 4 MB in the admin', function (): void {
    $project = Project::factory()->create();
    $this->actingAs(User::factory()->admin()->withMfa()->create());

    Livewire::test(EditProject::class, ['record' => $project->getRouteKey()])
        ->fillForm(['cover_path' => UploadedFile::fake()->image('huge.jpg', 100, 100)->size(4097)])
        ->call('save')
        ->assertHasFormErrors(['cover_path']);
});

it('processes leftovers and deletes orphaned uploads older than a day', function (): void {
    $disk = Storage::disk('public');
    $disk->put('projects/covers/legacy.png', png(1000, 600));
    $disk->put('projects/covers/abandoned.png', png(100, 100));
    $disk->put('projects/covers/uploading.png', png(100, 100));
    touch($disk->path('projects/covers/abandoned.png'), now()->subDays(2)->getTimestamp());
    // A cover saved before the pipeline existed: referenced, but never registered.
    Project::withoutEvents(fn () => Project::factory()->create(['cover_path' => 'projects/covers/legacy.png']));

    $this->artisan('portfolio:images')->assertSuccessful();

    $disk->assertMissing('projects/covers/abandoned.png');
    $disk->assertExists(['projects/covers/uploading.png', 'projects/covers/legacy.png', 'projects/covers/variants/legacy-640.webp']);
    expect(ImageAsset::query()->where('path', 'projects/covers/legacy.png')->sole()->processed_at)->not->toBeNull();
});
