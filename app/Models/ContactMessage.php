<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\InquiryType;
use Database\Factories\ContactMessageFactory;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property InquiryType $inquiry_type
 * @property Carbon|null $read_at
 */
final class ContactMessage extends Model
{
    /** @use HasFactory<ContactMessageFactory> */
    use HasFactory;

    // ip_address / user_agent are set explicitly by the action, never mass-assigned from input.
    protected $fillable = ['name', 'email', 'inquiry_type', 'message'];

    public function markAsRead(): void
    {
        if ($this->read_at === null) {
            $this->forceFill(['read_at' => now()])->save();
        }
    }

    /** @param Builder<self> $query */
    #[Scope]
    protected function unread(Builder $query): void
    {
        $query->whereNull('read_at');
    }

    protected function casts(): array
    {
        return [
            'inquiry_type' => InquiryType::class,
            'read_at' => 'datetime',
        ];
    }
}
