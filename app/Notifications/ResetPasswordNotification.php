<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;

/**
 * Branded password-reset email — replaces Laravel's default template with the
 * GolfTourney-styled one, while reusing the base class's token + URL handling.
 */
class ResetPasswordNotification extends ResetPassword
{
    public function toMail(mixed $notifiable): MailMessage
    {
        $expireMinutes = config('auth.passwords.'.config('auth.defaults.passwords').'.expire', 60);

        return (new MailMessage)
            ->subject('Reset your GolfTourney password')
            ->view('emails.reset-password', [
                'firstName' => ucfirst((string) ($notifiable->first_name ?? '')),
                'url' => $this->resetUrl($notifiable),
                'expireMinutes' => $expireMinutes,
            ]);
    }
}
