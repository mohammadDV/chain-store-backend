<?php

namespace Application\Api\User\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Domain\User\Models\User;

class ThankYouForRegistering extends Mailable
{
    use Queueable, SerializesModels;

    public $user;

    public function __construct(User $user)
    {
        $this->user = $user;
    }

    public function build()
    {
        return $this->markdown('emails.users.thankyou')
                    ->subject('Thank you for registering!');
    }
}
