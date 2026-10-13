<?php

declare(strict_types=1);

namespace App\Support\Images;

/**
 * Finds uploaded images (public disk, served under /storage) referenced from Markdown.
 */
final class MarkdownImages
{
    private const PREFIX = '/storage/';

    /**
     * Paths of images inside $directory that the Markdown links to.
     *
     * @return list<string>
     */
    public static function paths(string $markdown, string $directory): array
    {
        $pattern = '#'.preg_quote(self::PREFIX.trim($directory, '/').'/', '#').'[^\s)"\'<>]+#';
        preg_match_all($pattern, $markdown, $matches);

        return array_values(array_unique(array_map(
            static fn (string $url): string => substr($url, strlen(self::PREFIX)),
            $matches[0],
        )));
    }

    /** "https://site/storage/notes/attachments/a.png" → "notes/attachments/a.png"; null for other URLs. */
    public static function pathFromUrl(string $url): ?string
    {
        $host = parse_url($url, PHP_URL_HOST);
        $appHost = parse_url((string) config('app.url'), PHP_URL_HOST);
        if (is_string($host) && $host !== $appHost) {
            return null;
        }

        $path = (string) parse_url($url, PHP_URL_PATH);

        return str_starts_with($path, self::PREFIX) ? rawurldecode(substr($path, strlen(self::PREFIX))) : null;
    }
}
