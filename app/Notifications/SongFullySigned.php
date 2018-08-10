<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use App\Model\Song;

class SongFullySigned extends Notification implements ShouldQueue
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
     * Create a new message instance.
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
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toMail($notifiable)
    {
        $name = $this->data->first_name;
        $url = '';

        return (new MailMessage)
                    ->subject($this->song->title . " has been fully signed!")
                    ->greeting('Hey, ' . $name)
                    ->line('All parties have signed this document. Horray!');

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
            'type' => 'fully_signed'
        ];
    }

}
