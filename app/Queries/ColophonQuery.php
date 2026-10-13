<?php

declare(strict_types=1);

namespace App\Queries;

use App\Support\BuildMeta;

/**
 * Everything the colophon states, from measurements rather than copy: installed versions,
 * bytes from the build, the last CI run, and the commit. Mirrors `Colophon` in
 * resources/js/types/portfolio.ts (through ColophonResource).
 */
final class ColophonQuery
{
    /** @return array<string, mixed> */
    public static function get(): array
    {
        $build = BuildMeta::build();
        $quality = BuildMeta::quality();
        $repository = self::repository();
        $stack = (array) ($build['stack'] ?? []);

        return [
            'stack' => array_values(array_filter([
                ['name' => 'Laravel', 'version' => $stack['laravel'] ?? app()->version()],
                ['name' => 'PHP', 'version' => PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION.'.'.PHP_RELEASE_VERSION],
                ['name' => 'Inertia', 'version' => $stack['inertia'] ?? null],
                ['name' => 'Preact', 'version' => $stack['preact'] ?? null],
                ['name' => 'Filament', 'version' => $stack['filament'] ?? null],
                ['name' => 'Tailwind CSS', 'version' => $stack['tailwindcss'] ?? null],
                ['name' => 'Vite', 'version' => $stack['vite'] ?? null],
            ], static fn (array $item): bool => $item['version'] !== null)),
            'weight' => isset($build['weight']['js'], $build['weight']['css'], $build['weight']['fonts']) ? [
                'js' => (int) $build['weight']['js'],
                'css' => (int) $build['weight']['css'],
                'fonts' => (int) $build['weight']['fonts'],
                'fonts_amharic' => (int) ($build['weight']['fonts_amharic'] ?? $build['weight']['fonts']),
            ] : null,
            'build' => isset($build['built_at']) ? [
                'commit' => is_string($build['commit'] ?? null) ? substr($build['commit'], 0, 7) : null,
                'commit_url' => $repository !== null && is_string($build['commit'] ?? null) ? $repository.'/commit/'.$build['commit'] : null,
                'built_at' => (string) $build['built_at'],
            ] : null,
            'quality' => isset($quality['measured_at']) && (isset($quality['tests']) || isset($quality['lighthouse'])) ? [
                'tests' => isset($quality['tests']['passed']) ? [
                    'passed' => (int) $quality['tests']['passed'],
                    'total' => (int) ($quality['tests']['total'] ?? $quality['tests']['passed']),
                ] : null,
                'lighthouse' => isset($quality['lighthouse']['performance']) ? [
                    'pages' => (int) ($quality['lighthouse']['pages'] ?? 1),
                    'performance' => (int) $quality['lighthouse']['performance'],
                    'accessibility' => (int) ($quality['lighthouse']['accessibility'] ?? 0),
                    'best_practices' => (int) ($quality['lighthouse']['best_practices'] ?? 0),
                    'seo' => (int) ($quality['lighthouse']['seo'] ?? 0),
                ] : null,
                'measured_at' => (string) $quality['measured_at'],
            ] : null,
            'repository_url' => $repository,
        ];
    }

    private static function repository(): ?string
    {
        $url = config('portfolio.repo_url');

        return is_string($url) && $url !== '' ? rtrim($url, '/') : null;
    }
}
