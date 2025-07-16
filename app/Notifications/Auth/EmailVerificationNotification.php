<?php

namespace App\Notifications\Auth;

use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EmailVerificationNotification extends VerifyEmail
{
    /**
     * Get the verify email notification mail message for the given URL.
     *
     * @param  string  $url
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    protected function buildMailMessage($url)
    {
        return (new MailMessage)
            ->subject(__('messages.emails.verification_email.subject'))
            ->greeting(__('messages.emails.verification_email.greeting'))
            ->line(__('messages.emails.verification_email.line1'))
            ->action(__('messages.emails.verification_email.action'), $url)
            ->line(__('messages.emails.verification_email.line2'));
    }
}
