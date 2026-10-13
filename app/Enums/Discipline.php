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
            self::Engineering => 'Engineering',
            self::Design => 'Design',
        };
    }

    public function tagline(): string
    {
        return match ($this) {
            self::Engineering => 'Laravel · PHP',
            self::Design => 'Brand · UI',
        };
    }
}
