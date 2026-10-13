<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Profile;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/**
 * @mixin Profile
 */
final class ProfileResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'name' => $this->name,
            'headline' => $this->headline,
            'lede' => $this->lede,
            'bio' => $this->bio,
            'about_statement' => $this->about_statement,
            'location' => $this->location,
            'email' => $this->email,
            'is_available' => $this->is_available,
            'avatar_url' => $this->avatar_path ? Storage::disk('public')->url($this->avatar_path) : null,
            'cv_url' => $this->cv_path ? route('cv.download', absolute: false) : null,
            'social_links' => array_values(array_filter(
                $this->social_links ?? [],
                static fn (array $link): bool => filled($link['url'] ?? null),
            )),
            'facts' => $this->facts ?? [],
            'meta_description' => $this->meta_description ?? $this->lede,
        ];
    }
}
