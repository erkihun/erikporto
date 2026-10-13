<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Support\PortfolioCache;

/**
 * Any change to public content invalidates the cached page payloads,
 * so editors never see stale content after saving in the admin panel.
 */
trait FlushesPortfolioCache
{
    public static function bootFlushesPortfolioCache(): void
    {
        static::saved(static fn () => PortfolioCache::flush());
        static::deleted(static fn () => PortfolioCache::flush());
    }
}
