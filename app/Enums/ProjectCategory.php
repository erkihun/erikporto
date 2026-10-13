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
            self::Software => __('enums.category.software'),
            self::Design => __('enums.category.design'),
        };
    }

    /** Short descriptor shown on project cards. */
    public function kind(): string
    {
        return match ($this) {
            self::Software => __('enums.category.software_kind'),
            self::Design => __('enums.category.design_kind'),
        };
    }
}
