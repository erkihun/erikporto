<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\ProcessStep;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Keep in sync with `ProcessStep` in resources/js/types/portfolio.ts.
 *
 * @mixin ProcessStep
 */
final class ProcessStepResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
        ];
    }
}
