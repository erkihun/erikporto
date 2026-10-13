<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum InquiryType: string implements HasLabel
{
    case WebSystem = 'web_system';
    case BrandIdentity = 'brand_identity';
    case UiUx = 'ui_ux';
    case Other = 'other';

    public function getLabel(): string
    {
        return match ($this) {
            self::WebSystem => __('enums.inquiry.web_system'),
            self::BrandIdentity => __('enums.inquiry.brand_identity'),
            self::UiUx => __('enums.inquiry.ui_ux'),
            self::Other => __('enums.inquiry.other'),
        };
    }

    /** @return list<array{value: string, label: string}> */
    public static function options(): array
    {
        return array_map(
            static fn (self $case): array => ['value' => $case->value, 'label' => $case->getLabel()],
            self::cases(),
        );
    }
}
