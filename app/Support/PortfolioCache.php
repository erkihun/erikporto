<?php

declare(strict_types=1);

namespace App\Support;

use Closure;
use Illuminate\Support\Facades\Cache;

/**
 * Versioned cache keys: flushing bumps the version instead of tagging,
 * which works on every cache store (file, database, redis).
 */
final class PortfolioCache
{
    private const VERSION_KEY = 'portfolio:version';

    /**
     * @template T
     *
     * @param  Closure(): T  $callback
     * @return T
     */
    public static function remember(string $key, Closure $callback): mixed
    {
        return Cache::remember(
            self::key($key),
            now()->addSeconds((int) config('portfolio.cache_ttl')),
            $callback,
        );
    }

    public static function flush(): void
    {
        Cache::forever(self::VERSION_KEY, self::version() + 1);
    }

    private static function key(string $key): string
    {
        return sprintf('portfolio:v%d:%s', self::version(), $key);
    }

    private static function version(): int
    {
        return (int) Cache::get(self::VERSION_KEY, 1);
    }
}
