<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum ProjectCategory: string implements HasLabel
{
    case Software = 'software';
    case Design = 'design';

    public function getLabel(): string
    {
        return match ($this) {
            self::Software => 'Software',
            self::Design => 'Design',
        };
    }

    /** Short descriptor shown on project cards. */
    public function kind(): string
    {
        return match ($this) {
            self::Software => 'Web system',
            self::Design => 'Brand identity',
        };
    }
}
