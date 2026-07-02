<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PlayerInvitation extends Notification
{
    use Queueable;

    public function __construct(public string $token, public string $leagueName) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $url = route('invite.accept', ['token' => $this->token, 'email' => $notifiable->email]);

        return (new MailMessage)
            ->subject("You're invited to {$this->leagueName} on GolfTourney")
            ->view('emails.player-invitation', [
                'firstName' => ucfirst((string) $notifiable->first_name),
                'leagueName' => $this->leagueName,
                'url' => $url,
            ]);
    }
}
