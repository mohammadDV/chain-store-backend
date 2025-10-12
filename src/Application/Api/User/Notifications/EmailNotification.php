<?php

namespace Application\Api\User\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class EmailNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $title;
    protected $content;
    protected $actionUrl;

    public function __construct($title, $content, $actionUrl = null)
    {
        $this->title = $title;
        $this->content = $content;
        $this->actionUrl = $actionUrl;
    }

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject($this->title)
            ->view('emails.custom-notification', [
                'title' => $this->title,
                'content' => $this->content,
                'actionUrl' => $this->actionUrl ?? config('app.url'),
            ]);
    }
}