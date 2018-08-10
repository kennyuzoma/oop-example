<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use App\Model\Song;

class SongAmended extends Notification implements ShouldQueue
{
    /**
     * The user instance.
     *
     * @var User
     */
    public $user;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($user, Song $song)
    {
        $this->song = $song;
        $this->user = $user;
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
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toMail($notifiable)
    {
        $name = ucfirst($this->user['first_name']);
        $url = 'http://google.com';

        return (new MailMessage)
                    ->subject($this->song->title . " has been fully signed!")
                    ->greeting('Hey, ' . $name)
                    ->line('User has amended this song split.')
                    ->action('View Amendment', $url);

    }

}
