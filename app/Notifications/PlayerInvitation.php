<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PlayerInvitation extends Notification
{
    use Queueable;

    public function __construct(public string $token) {}

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
            ->subject('Set up your GolfTourney login')
            ->greeting('Hi '.ucfirst((string) $notifiable->first_name).',')
            ->line('You’ve been invited to manage your handicap on GolfTourney — track your rounds and keep your Handicap Index up to date.')
            ->action('Set up your account', $url)
            ->line('If you weren’t expecting this, you can ignore this email.');
    }
}
