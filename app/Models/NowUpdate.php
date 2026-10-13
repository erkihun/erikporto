<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\FlushesPortfolioCache;
use App\Models\Concerns\HasPublicationDate;
use App\Support\Markdown\MarkdownRenderer;
use Database\Factories\NowUpdateFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * One entry of the /now page. The newest published entry is the current one;
 * older entries are the page's history. Not announced on Telegram.
 */
final class NowUpdate extends Model
{
    /** @use HasFactory<NowUpdateFactory> */
    use FlushesPortfolioCache, HasFactory, HasPublicationDate;

    // body_html is rendered on save and never accepted as input.
    protected $fillable = ['body', 'published_at'];

    /** Plain-text opening of the rendered body, for collapsed history rows and admin lists. */
    public function summary(int $limit = 160): string
    {
        $text = html_entity_decode(strip_tags((string) $this->body_html), ENT_QUOTES | ENT_HTML5);

        return Str::limit(Str::squish($text), $limit);
    }

    protected static function booted(): void
    {
        self::saving(static function (NowUpdate $update): void {
            if ($update->isDirty('body') || blank($update->body_html)) {
                $update->forceFill(['body_html' => app(MarkdownRenderer::class)->render((string) $update->body)->html]);
            }
        });
    }

    protected function casts(): array
    {
        return ['published_at' => 'datetime'];
    }
}
