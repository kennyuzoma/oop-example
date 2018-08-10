<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class UserConfirmEmail extends Notification implements ShouldQueue
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
        $button_link = config('app.url').'/confirm?code='.$this->data['code'];
        $name = $this->data['name'];

        return (new MailMessage)
                    ->subject("Confirm your " . config('app.name') . " account")
                    ->greeting('Hey, ' . $name)
                    ->line("Thank you for registering for " . config('app.name') . ". Please confirm your account by clicking the link.")
                    ->action('Confirm Account', $button_link);
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
