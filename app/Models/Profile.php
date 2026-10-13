<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\FlushesPortfolioCache;
use Database\Factories\ProfileFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Singleton holding the site owner's public identity.
 *
 * @property list<string> $bio
 * @property list<array{label: string, url: ?string, handle: ?string}>|null $social_links
 * @property list<array{value: string, label: string}>|null $facts
 */
final class Profile extends Model
{
    /** @use HasFactory<ProfileFactory> */
    use FlushesPortfolioCache, HasFactory;

    protected $fillable = [
        'name', 'headline', 'lede', 'bio', 'about_statement', 'location', 'email',
        'is_available', 'avatar_path', 'cv_path', 'social_links', 'facts', 'meta_description',
    ];

    public static function current(): ?self
    {
        return self::query()->oldest('id')->first();
    }

    protected function casts(): array
    {
        return [
            'bio' => 'array',
            'social_links' => 'array',
            'facts' => 'array',
            'is_available' => 'boolean',
        ];
    }
}
