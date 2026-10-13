<?php

declare(strict_types=1);

namespace App\Data;

use App\Enums\InquiryType;

final readonly class ContactMessageData
{
    public function __construct(
        public string $name,
        public string $email,
        public InquiryType $inquiryType,
        public string $message,
        public ?string $ipAddress = null,
        public ?string $userAgent = null,
    ) {}
}
