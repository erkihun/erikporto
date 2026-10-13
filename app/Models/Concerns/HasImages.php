<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Actions\Images\RegisterImage;
use App\Support\Images\ImageReferences;
use App\Support\Images\MarkdownImages;
use Illuminate\Database\Eloquent\Model;

/**
 * Uploaded images owned by a model (public disk). New images are registered before
 * the save (so Markdown renders them with width/height) and processed on the queue;
 * images that were replaced, removed from a body or belong to a deleted record are
 * deleted with their variants after the transaction commits.
 *
 * @mixin Model
 */
trait HasImages
{
    /**
     * Attributes holding one image path or a list of them.
     *
     * @return list<string>
     */
    abstract public function imageAttributes(): array;

    /**
     * Markdown attributes whose uploaded images are managed too, with the directory
     * the editor uploads them to (images elsewhere are only linked, never deleted).
     *
     * @return array<string, string>
     */
    public function markdownImageAttributes(): array
    {
        return [];
    }

    /** Re-renders stored HTML that embeds images; models with Markdown override it. */
    public function refreshRenderedMarkdown(): void {}

    public static function bootHasImages(): void
    {
        static::saving(static function (Model $model): void {
            foreach (array_diff($model->imagePaths(), $model->exists ? $model->imagePaths(original: true) : []) as $path) {
                app(RegisterImage::class)->handle($path);
            }
        });

        static::saved(static function (Model $model): void {
            ImageReferences::releaseAfterCommit(array_values(array_diff($model->imagePaths(original: true), $model->imagePaths())));
        });

        static::deleted(static function (Model $model): void {
            ImageReferences::releaseAfterCommit([...$model->imagePaths(original: true), ...$model->imagePaths()]);
        });
    }

    /** @return list<string> */
    public function imagePaths(bool $original = false): array
    {
        $value = fn (string $attribute): mixed => $original ? $this->getOriginal($attribute) : $this->getAttribute($attribute);

        $paths = [];
        foreach ($this->imageAttributes() as $attribute) {
            foreach ((array) $value($attribute) as $path) {
                if (is_string($path) && $path !== '') {
                    $paths[] = $path;
                }
            }
        }

        foreach ($this->markdownImageAttributes() as $attribute => $directory) {
            array_push($paths, ...MarkdownImages::paths((string) $value($attribute), $directory));
        }

        return array_values(array_unique($paths));
    }
}
