<?php
namespace App\Library\Services;

use Illuminate\Http\Request;
use \MailerLiteApi\MailerLite as ML;
use \App\Model\User;
/**
* 
*/
class MailerLite
{
    public static $mailerlite;
    

    public static function subscribe(User $user)
    {
        $mailerlite = new \MailerLiteApi\MailerLite(config('email.mailerlite_api_key'));

        try {
            // Add subsriber
            $subscriber = $mailerlite->groups()->addSubscriber(config('email.mailerlite_group_id'), [
              'email' => $user->email,
              'type' => 'active',
              'fields' => [
                    'name' => $user->first_name,
                    'last_name' => $user->last_name,
                ]
            ]);

            // set the subscriber id
            $user->email_subscriber_id = $subscriber->id;
            $user->save();

            return true;

        } catch(\Exception $e) {
            return false;
       }

    }

    public static function unsubscribe(User $user)
    {
        $mailerlite = new \MailerLiteApi\MailerLite(config('email.mailerlite_api_key'));

        // remove subscriber
        try {

            $mailerlite->groups()->removeSubscriber(config('email.mailerlite_group_id'), $user->email_subscriber_id);

            return true;

        } catch(\Exception $e) {
            return false;
        }
        
    }

}