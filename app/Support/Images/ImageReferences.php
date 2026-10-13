<?php

declare(strict_types=1);

namespace App\Support\Images;

use App\Actions\Images\DeleteImage;
use App\Models\Note;
use App\Models\NowUpdate;
use App\Models\Profile;
use App\Models\Project;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * Which uploaded images are still used, so replaced or deleted ones never linger.
 */
final class ImageReferences
{
    /** Models that own uploaded images (they use the HasImages concern). */
    public const OWNERS = [Profile::class, Project::class, Note::class, NowUpdate::class];

    /** Public-disk directories that hold only managed uploads (see the Filament forms). */
    public const DIRECTORIES = ['profile', 'projects/covers', 'projects/gallery', 'notes/covers', 'notes/attachments', 'now/attachments'];

    /** @return array<string, true> every image path some record uses right now */
    public static function inUse(): array
    {
        $paths = [];
        foreach (self::OWNERS as $owner) {
            foreach ($owner::query()->get() as $record) {
                foreach ($record->imagePaths() as $path) {
                    $paths[$path] = true;
                }
            }
        }

        return $paths;
    }

    /**
     * Deletes these images (original, variants, row) once the current transaction commits,
     * unless another record still uses them.
     *
     * @param  list<string>  $paths
     */
    public static function releaseAfterCommit(array $paths): void
    {
        $paths = array_values(array_unique(array_filter($paths)));
        if ($paths === []) {
            return;
        }

        DB::afterCommit(static function () use ($paths): void {
            $inUse = self::inUse();
            foreach ($paths as $path) {
                if (! isset($inUse[$path])) {
                    app(DeleteImage::class)->handle($path);
                }
            }
        });
    }

    /** Re-renders the Markdown bodies that show this image, so their <img> gets the new srcset. */
    public static function rerenderMarkdownUsing(string $path): void
    {
        foreach (self::OWNERS as $owner) {
            /** @var Model $prototype */
            $prototype = new $owner;
            foreach (array_keys($prototype->markdownImageAttributes()) as $attribute) {
                $owner::query()->where($attribute, 'like', '%'.$path.'%')->get()->each->refreshRenderedMarkdown();
            }
        }
    }
}
