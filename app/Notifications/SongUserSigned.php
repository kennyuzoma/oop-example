<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class SongUserSigned extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Data passed in
     *
     * @var data
     */
    public $data;

    public $song;

    public $writer;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($writer, $song, $data)
    {
        $this->writer = $writer;
        $this->song = $song;
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
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        $name = $this->data->first_name;
        $url = '';

        return (new MailMessage)
                    ->subject($this->writer->first_name . " signed the Split sheet agreement")
                    ->greeting('Hey, ' . $name)
                    ->line($this->writer->first_name . ' signed the '. $this->song->title. ' split sheet.')
                    ->action('View the split sheet', $url);
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
            'song_id' => $this->song->id,
            'song_title' => $this->song->title,
            'user' => $this->writer->id,
            'type' => 'signed'
        ];
    }
}
