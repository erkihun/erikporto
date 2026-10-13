<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\FlushesPortfolioCache;
use App\Models\Concerns\HasImages;
use App\Models\Concerns\HasLocalizedContent;
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
    use FlushesPortfolioCache, HasFactory, HasImages, HasLocalizedContent;

    /** @var list<string> English and Amharic per field; the slug, URLs, tags and enums are not translated. */
    public array $translatable = ['headline', 'lede', 'bio', 'about_statement', 'location', 'meta_description'];

    protected $fillable = [
        'name', 'headline', 'lede', 'bio', 'about_statement', 'location', 'email',
        'is_available', 'avatar_path', 'cv_path', 'social_links', 'facts', 'meta_description',
    ];

    public static function current(): ?self
    {
        return self::query()->oldest('id')->first();
    }

    /** The portrait; the CV is a private PDF and not part of the image pipeline. */
    public function imageAttributes(): array
    {
        return ['avatar_path'];
    }

    protected function casts(): array
    {
        return [
            'social_links' => 'array',
            'facts' => 'array',
            'is_available' => 'boolean',
        ];
    }
}
