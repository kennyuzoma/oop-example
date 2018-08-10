<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;
use App\Model\Song;
use App\Model\User;
use Vinkla\Hashids\Facades\Hashids;
use Illuminate\Contracts\Queue\ShouldQueue;


class NewSong extends Notification implements ShouldQueue
{
    use Queueable;

    protected $song;
    protected $user;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(Song $song, $user)
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
        // if user hasnt signed up
        if($this->user['status'] == 3) {

            $query_string = http_build_query([
                'signup_email' => $this->user['email'],
                'signup_code' => Hashids::encode($this->user['id']),
                
                // for getting the temp value data of the user
                'temp_user_code' => urlencode(base64_encode($this->user['inviter_type'] . ';' . Hashids::encode($this->user['inviter_id'])))
            ]);

            $url_segments = '/signup?' . $query_string;

        } else {
            $url_segments = '/login&?next=' . urlencode('song/' . $this->song->id);
        }

        $url = config('app.url') . $url_segments;
        $name = ucfirst($this->user['first_name']);
        $invited_by = User::find($this->song->created_by['user_id'])->first_name;

        return (new MailMessage)
                    ->subject('You were invited to sign "' . $this->song->title . '"')
                    ->greeting('Hey, ' . $name)
                    ->line($invited_by . ' invited you to sign the split sheet. Click the link below to sign.')
                    ->action('Sign Splitsheet', $url);

    }

    public function toArray($notifiable)
    {
        return [
            'song_id' => $this->song->id,
            'song_title' => $this->song->title,
            'type' => 'invited_to_sign'
        ];
    }

}
