<?php

namespace Application\Api\User\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class ThankYouForRegistering extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct() {}

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject(__('site.Welcome to') . ' ' . config('app.name'))
            ->view('emails.users.thankyou')
            ->with([
                'user' => $notifiable,
            ]);
    }
}