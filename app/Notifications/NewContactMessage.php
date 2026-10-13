<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\ContactMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

final class NewContactMessage extends Notification implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /** @var list<int> */
    public array $backoff = [60, 300];

    public function __construct(public readonly ContactMessage $contactMessage)
    {
        $this->afterCommit();
    }

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $m = $this->contactMessage;

        return (new MailMessage)
            ->subject('New enquiry: '.$m->inquiry_type->getLabel().' from '.$m->name)
            ->replyTo($m->email, $m->name)
            ->greeting('New message from your portfolio')
            ->line('**From:** '.e($m->name).' <'.e($m->email).'>')
            ->line('**Type:** '.$m->inquiry_type->getLabel())
            ->line(e(Str::limit($m->message, 2000)))
            ->action('Open in admin', url('/admin/contact-messages/'.$m->getKey()));
    }
}
