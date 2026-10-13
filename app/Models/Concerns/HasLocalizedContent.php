<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use Spatie\Translatable\HasTranslations;

/**
 * spatie/laravel-translatable with one rule added: a blank translation (null, '' or [])
 * removes that locale instead of storing it, so reads fall back to English and the JSON
 * stays clean when an editor leaves the Amharic tab empty.
 */
trait HasLocalizedContent
{
    use HasTranslations;

    /** @param array<string, mixed> $translations */
    public function setTranslations(string $key, array $translations): self
    {
        $this->guardAgainstNonTranslatableAttribute($key);

        if ($translations === []) {
            $this->attributes[$key] = '[]';

            return $this;
        }

        $current = $this->getTranslations($key);
        foreach ($translations as $locale => $value) {
            if ($value === null || $value === '' || $value === []) {
                unset($current[$locale]);
            } else {
                $current[$locale] = $value;
            }
        }

        $this->attributes[$key] = $current === [] ? '[]' : json_encode($current, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return $this;
    }
}
