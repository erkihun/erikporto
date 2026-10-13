<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Note;
use Illuminate\Http\Request;

/**
 * Keep in sync with `NoteDetail` in resources/js/types/portfolio.ts.
 * `body_html` is produced by MarkdownRenderer with raw HTML escaped.
 *
 * @mixin Note
 */
final class NoteResource extends NoteSummaryResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            ...parent::toArray($request),
            'body_html' => $this->body_html,
            'toc' => $this->toc ?? [],
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
