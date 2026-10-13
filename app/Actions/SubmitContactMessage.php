<?php

declare(strict_types=1);

namespace App\Actions;

use App\Data\ContactMessageData;
use App\Models\ContactMessage;
use App\Models\Profile;
use App\Notifications\NewContactMessage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

final readonly class SubmitContactMessage
{
    public function handle(ContactMessageData $data): ContactMessage
    {
        $message = DB::transaction(static function () use ($data): ContactMessage {
            $message = new ContactMessage([
                'name' => $data->name,
                'email' => $data->email,
                'inquiry_type' => $data->inquiryType,
                'message' => $data->message,
            ]);

            $message->forceFill([
                'ip_address' => $data->ipAddress,
                'user_agent' => $data->userAgent,
            ])->save();

            return $message;
        });

        $recipient = config('portfolio.notify_email') ?: Profile::current()?->email;

        // The message is always kept in the admin inbox; email is a convenience.
        if (filled($recipient)) {
            Notification::route('mail', $recipient)->notify(new NewContactMessage($message));
        }

        return $message;
    }
}
