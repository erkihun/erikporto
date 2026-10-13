<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum ExperienceType: string implements HasLabel
{
    case Work = 'work';
    case Education = 'education';

    public function getLabel(): string
    {
        return match ($this) {
            self::Work => __('enums.experience.work'),
            self::Education => __('enums.experience.education'),
        };
    }
}
