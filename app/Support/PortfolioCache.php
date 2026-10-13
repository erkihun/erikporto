<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Note;
use App\Models\NowUpdate;
use Closure;
use Illuminate\Support\Facades\Cache;

/**
 * Versioned cache keys: flushing bumps the version instead of tagging,
 * which works on every cache store (file, database, redis).
 *
 * Entries also expire the moment the next scheduled note or /now update goes
 * live, so scheduled publishing needs no cron job to refresh pages.
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
        $cacheKey = self::key($key);
        $cached = Cache::get($cacheKey);

        if ($cached !== null) {
            return $cached;
        }

        $value = $callback();

        if ($value !== null) {
            Cache::put($cacheKey, $value, now()->addSeconds(self::ttl()));
        }

        return $value;
    }

    public static function flush(): void
    {
        Cache::forever(self::VERSION_KEY, self::version() + 1);
    }

    private static function ttl(): int
    {
        $untilScheduled = array_filter(
            [Note::secondsUntilNextScheduled(), NowUpdate::secondsUntilNextScheduled()],
            static fn (?int $seconds): bool => $seconds !== null,
        );

        return min([(int) config('portfolio.cache_ttl'), ...$untilScheduled]);
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
