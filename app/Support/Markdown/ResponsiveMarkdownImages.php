<?php

declare(strict_types=1);

namespace App\Support\Markdown;

use App\Models\ImageAsset;
use App\Support\Images\MarkdownImages;
use League\CommonMark\Event\DocumentParsedEvent;
use League\CommonMark\Extension\CommonMark\Node\Inline\Image;

/**
 * Markdown images load lazily, and uploaded ones get their intrinsic size (no layout
 * shift) plus the WebP srcset once ProcessUploadedImage has written the variants.
 */
final class ResponsiveMarkdownImages
{
    /** The .prose column is at most 720px wide. */
    private const SIZES = '(min-width: 760px) 720px, 100vw';

    public function __invoke(DocumentParsedEvent $event): void
    {
        $images = [];
        foreach ($event->getDocument()->iterator() as $node) {
            if ($node instanceof Image) {
                $images[] = $node;
            }
        }

        if ($images === []) {
            return;
        }

        $paths = array_values(array_filter(array_map(static fn (Image $image): ?string => MarkdownImages::pathFromUrl($image->getUrl()), $images)));
        $assets = $paths === [] ? collect() : ImageAsset::query()->whereIn('path', $paths)->get()->keyBy('path');

        foreach ($images as $image) {
            $image->data->set('attributes/loading', 'lazy');
            $image->data->set('attributes/decoding', 'async');

            $asset = $assets->get(MarkdownImages::pathFromUrl($image->getUrl()) ?? '');
            if (! $asset instanceof ImageAsset) {
                continue;
            }

            $image->data->set('attributes/width', (string) $asset->width);
            $image->data->set('attributes/height', (string) $asset->height);

            $srcset = $asset->srcset();
            if ($srcset !== null) {
                $image->data->set('attributes/srcset', $srcset);
                $image->data->set('attributes/sizes', self::SIZES);
            }
        }
    }
}
