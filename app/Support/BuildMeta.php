<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Facts measured outside PHP, for the colophon:
 * - public/build/build-meta.json, written by scripts/build-meta.mjs on every `npm run build`;
 * - storage/app/quality.json, the last CI run (scripts/quality-report.mjs), fetched on deploy.
 * Both are optional; a missing or broken file means that part is not shown.
 */
final class BuildMeta
{
    /** @return array<string, mixed>|null */
    public static function build(): ?array
    {
        return self::read((string) config('portfolio.build_meta'));
    }

    /** @return array<string, mixed>|null */
    public static function quality(): ?array
    {
        return self::read((string) config('portfolio.quality_report'));
    }

    /** @return array<string, mixed>|null */
    private static function read(string $path): ?array
    {
        if (! is_file($path)) {
            return null;
        }

        $data = json_decode((string) file_get_contents($path), true);

        return is_array($data) ? $data : null;
    }
}
