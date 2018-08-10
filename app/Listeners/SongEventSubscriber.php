<?php

namespace App\Listeners;

use App\Model\User;
use App\Model\Song;
use App\Notifications\SongViewed as NotiSongViewed;
use Notification;
use App\Library\Services\Audit;
use App\Jobs\CreateAgreementPDF;

class SongEventSubscriber
{
    public function recordAuditViewed($event)
    {
        // store manual audit view
        Audit::oneTime()->record('viewed', $event->song);
    }

    public function recordAuditEmailed($event)
    {
        // store manual audit emailed
        Audit::user($event->user['id'])->record('emailed', $event->song);
    }

    public function recordAuditCompleted($event)
    {
        // new audit record
        Audit::noUser()->record('completed', $event->song);
    }

    public function sendViewedNotification($event)
    {
        // get the user
        $user = User::find($event->song->created_by['user_id']);

        // notifications
        Notification::send($user, new NotiSongViewed($event->song));
    }

    public function sendCreatedNotification($event)
    {
        // get the user
        $user = User::find($event->user['id']);

        // Send email, this is queued
        Notification::send($user, new \App\Notifications\NewSong($event->song, $event->user));
    }

    public function createAgreementPDF($event)
    {
        $data = Song::with('writers')->find($event->song->id)->toArray();

        // Create PDF... this is queued
        CreateAgreementPDF::dispatch($event->song, $data);
    }

    /**
     * register the events with the listeners
     * @param  [type] $events [description]
     * @return [type]         [description]
     */
    public function subscribe($events)
    {
        $subscriber = 'App\Listeners\SongEventSubscriber';

        // Viewed Song
        $events->listen(
            'App\Events\SongViewed',
            $subscriber . '@sendViewedNotification'
        );

        $events->listen(
            'App\Events\SongViewed',
            $subscriber . '@recordAuditViewed'
        );

        // Song Created
        $events->listen(
            'App\Events\SongCreated',
            $subscriber . '@sendCreatedNotification'
        );

        $events->listen(
            'App\Events\SongCreated',
            $subscriber . '@recordAuditEmailed'
        );

        // Song Completely Signed
        $events->listen(
            'App\Events\SongCompletelySigned',
            $subscriber . '@recordAuditCompleted'
        );

        $events->listen(
            'App\Events\SongCompletelySigned',
            $subscriber . '@createAgreementPDF'
        );

        
    }

}