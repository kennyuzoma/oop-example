<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class UserChangeEmailRequestEmail extends Notification implements ShouldQueue
{
    use Queueable;

    public $data;
    
    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($data)
    {
        $this->data = $data;
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
        $url_segment = 'email='.$this->data['email'].'&code='.$this->data['confirmation_code'];
        $url = config('app.url') . '?' . $url_segment;

        $name = User::find($this->data['user_id'])->first_name;


        return (new MailMessage)
                    ->subject("Change email request")
                    ->greeting("Hey, " . $name)
                    ->line('You have requested to change your email. Please click this link to change your email')
                    ->action('Confirm Email Change', $url);
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
