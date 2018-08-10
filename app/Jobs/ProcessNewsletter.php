<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Library\Services\MailerLite;
use App\Model\User;

class ProcessNewsletter implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $user;
    protected $action;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(User $user, $action)
    {
        $this->user = $user;
        $this->action = $action;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        if($this->action == 'subscribe') {

            MailerLite::subscribe($this->user);
        
        } elseif($this->action == 'unsubscribe') {
        
            MailerLite::unsubscribe($this->user);
        
        }
    }
}
