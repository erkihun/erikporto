<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\NowUpdate;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Keep in sync with `NowUpdate` in resources/js/types/portfolio.ts.
 * `body_html` is produced by MarkdownRenderer with raw HTML escaped.
 *
 * @mixin NowUpdate
 */
final class NowUpdateResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'published_at' => $this->published_at?->toIso8601String(),
            'body_html' => $this->body_html,
            'summary' => $this->resource->summary(),
        ];
    }
}
