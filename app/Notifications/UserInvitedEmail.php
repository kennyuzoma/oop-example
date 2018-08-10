<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class UserInvitedEmail extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Data passed in
     *
     * @var data
     */
    public $data;

    /**
     * Create a new message instance.
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
        $url = config('app.url').'/signup';

        return (new MailMessage)
                    ->subject('You have been invited to SiteName!')
                    ->greeting('Hey, ' . $this->data['email'])
                    ->line('Thank you for creating account on our website, there is one more step before you can use it, you need to activate your account by clicking the link below. Once you click the button, just login to your account and you are set to go.')
                    ->action('Join ' . config('app.name'), $url);
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
