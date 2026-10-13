<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Experience;
use App\Support\Localized;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Experience
 */
final class ExperienceResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type->value,
            'type_label' => $this->type->getLabel(),
            'title' => $this->title,
            'organization' => $this->organization,
            'location' => Localized::text($this->location),
            'period' => $this->period(),
            'description' => Localized::text($this->description),
        ];
    }
}
