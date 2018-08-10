<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class UserResetPasswordEmail extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * The Password Reset instance.
     *
     * @var User
     */
    public $password_reset;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(PasswordReset $password_reset)
    {
        $this->password_reset = $password_reset;
    }


    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        $name = 'Kenny';
        $url = 'http://google.com?token='.$this->password_reset->token;

        return (new MailMessage)
                    ->subject('Password Reset Request')
                    ->greeting('Hey, ' . $name)
                    ->line('You asked to reset your password. Click button to reset your password')
                    ->action('Reset your password', $url);

    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            //
        ];
    }
}
