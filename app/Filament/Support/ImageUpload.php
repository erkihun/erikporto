<?php

declare(strict_types=1);

namespace App\Filament\Support;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\MarkdownEditor;

/**
 * Image uploads for the public site: JPEG, PNG or WebP up to 4 MB on the public disk.
 * SVG is refused (it can carry scripts). After saving, the image pipeline strips EXIF
 * and writes WebP variants (App\Actions\Images); replaced files are deleted.
 */
final class ImageUpload
{
    public const TYPES = ['image/jpeg', 'image/png', 'image/webp'];

    /** Kilobytes. */
    public const MAX_SIZE = 4096;

    public static function make(string $name, string $directory): FileUpload
    {
        return FileUpload::make($name)
            ->image()
            ->acceptedFileTypes(self::TYPES)
            ->maxSize(self::MAX_SIZE)
            ->disk('public')
            ->directory($directory)
            ->visibility('public');
    }

    /** Same rules for images pasted or dropped into a Markdown editor. */
    public static function markdown(MarkdownEditor $editor, string $directory): MarkdownEditor
    {
        return $editor
            ->fileAttachmentsDisk('public')
            ->fileAttachmentsDirectory($directory)
            ->fileAttachmentsAcceptedFileTypes(self::TYPES)
            ->fileAttachmentsMaxSize(self::MAX_SIZE);
    }
}
