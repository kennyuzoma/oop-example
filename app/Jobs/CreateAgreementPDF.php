<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Model\Song;

class CreateAgreementPDF implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $song;
    protected $data;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Song $song, $data)
    {
        $this->song = $song;
        $this->data = $data;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
         // send a array parameter
        $file_name = sha1($this->song->id) . '.pdf';

        // store
        $pdf = \PDF::loadView('split', $this->data);

        \Storage::put('documents/splits/' . $file_name, $pdf->output());

        // create the record
        $attachment = \App\Attachment::create([
            'attachable_type' => 'App\\Song',
            'attachable_id' => $this->song->id,
            'file' => $file_name,
            'type' => 'final'
        ]);

        // save to the song
        $this->song->attachment_id = $attachment->id;
        $this->song->save();
    }
}
