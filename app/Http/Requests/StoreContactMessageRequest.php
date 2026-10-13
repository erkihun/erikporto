<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Data\ContactMessageData;
use App\Enums\InquiryType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

final class StoreContactMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // public form; abuse is handled by throttling and the honeypot
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'string', 'email:rfc', 'max:190'],
            'inquiry_type' => ['required', Rule::enum(InquiryType::class)],
            'message' => ['required', 'string', 'min:20', 'max:5000'],
            'website' => ['nullable', 'string', 'max:255'], // honeypot, must stay empty
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'message.min' => 'Tell me a little more (at least :min characters).',
        ];
    }

    /** Bots fill every field; humans never see the honeypot. */
    public function isSpam(): bool
    {
        return $this->filled('website');
    }

    public function toData(): ContactMessageData
    {
        return new ContactMessageData(
            name: Str::squish($this->string('name')->toString()),
            email: Str::lower($this->string('email')->trim()->toString()),
            inquiryType: $this->enum('inquiry_type', InquiryType::class),
            message: trim($this->string('message')->toString()),
            ipAddress: $this->ip(),
            userAgent: Str::limit((string) $this->userAgent(), 250, ''),
        );
    }
}
