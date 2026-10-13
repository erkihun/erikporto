<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\InquiryType;
use App\Models\ContactMessage;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ContactMessage> */
final class ContactMessageFactory extends Factory
{
    protected $model = ContactMessage::class;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->safeEmail(),
            'inquiry_type' => InquiryType::WebSystem,
            'message' => fake()->paragraph(),
        ];
    }
}
