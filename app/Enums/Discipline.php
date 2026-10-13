<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum Discipline: string implements HasLabel
{
    case Engineering = 'engineering';
    case Design = 'design';

    public function getLabel(): string
    {
        return match ($this) {
            self::Engineering => __('enums.discipline.engineering'),
            self::Design => __('enums.discipline.design'),
        };
    }

    public function tagline(): string
    {
        return match ($this) {
            self::Engineering => __('enums.discipline.engineering_tagline'),
            self::Design => __('enums.discipline.design_tagline'),
        };
    }
}
