<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Model\Song;
use App\Model\Signature;
use App\Model\User;
use App\Events\SongViewed;
use App\Events\SongCreated;
use App\Events\SongCompletelySigned;
use Notification;

use App\Library\Services\Audit;

class SongController extends Controller
{
    /**
     * Get all records
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
	public function all(Request $request)
	{
		return response()->json(Song::paginate($request->get('limit')));
	}

    /**
     * Get the song model
     * @param Request $request
     * @param Song $song
     * @param bool $raw
     * @return Song|\Illuminate\Http\JsonResponse
     */
	public function get(Request $request, Song $song, $raw = false)
	{
        // fire viewed song event
        event(new SongViewed($song));

        // return song
        if($raw) {
            return $song;
        } 

		return response()->json($song);
	}

	/**
     * Adds a record to the database
     * @param Request $request Request Object
     * @return object
     */
	public function add(Request $request)
	{
		//validation
		$validator = Validator::make($request->all(), Song::$rules);

        if ($validator->fails()) {
            return response()->json([
            	'message' => $validator->errors()
            ], Response::HTTP_BAD_REQUEST);
        }

        // created by array
        $created_by['user_id'] = $request->user()->id;

        // add a creator to the song
        if(!is_null($team_id = $request->header('X-Team-Id'))) {
            $created_by['team_id'] = (int) $team_id;
        } 

        // add to the request
        $request->merge(['created_by' => $created_by]);

        // empty by default
        $songwriter = [];

        // get the songwriter data
        if(!is_null($songwriter_data = $request->get('songwriter_data'))) {

            if(array_sum(array_column($songwriter_data, 'percentage')) != 100) {
                return response()->json([
                    'message' => 'Total percentage for all parties must equal 100 percent'
                ], Response::HTTP_BAD_REQUEST);
            } 

            // loop thru the songwriter
            foreach($songwriter_data as $s) {

                // change type
                if($s['type'] == 'songwriter') {
                    $s['type'] = 'user';
                }

                // add to the array
                $songwriter[] = $s;
            }

        }

        // create the song
        if(!$song = Song::create($request->except('songwriter_data'))) {
        	return response()->json([
        		'message' => 'There was a problem creating this song'
        	]);
        }
        
        // if there is a team header id present
        if(isset($team_id)) {
            // attach to the team
            $song->teams()->attach(\App\Team::find($team_id));
        } else {
            // attach to the user
            $song->users()->attach($request->user());
        }
       
        $this->processSongwriters($songwriters);

        // return the song
		return response()->json($song);
	}

    /**
     * Edit record
     * @param Request $request
     * @param Song $song
     * @return \Illuminate\Http\JsonResponse
     */
	public function put(Request $request, Song $song)
	{
        // get writers to amend to data
        $song->writers;
        $amend_old_data = $song->toArray();

		 //validation
        $validator = Validator::make($request->all(), Song::$rules);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors()
            ], Response::HTTP_BAD_REQUEST);
        }

        if(is_null($songwriter_data = $request->get('songwriter_data')) || count($songwriter_data) == 0) {

            // remove all songwriters from the song.
            $song->writers()->detach();

        } else {

            // if there is only 1 songwriter... delete the rest and 
            // make this one have 100 percent
            if(count($songwriter_data) == 1) {

                // set to 100 by default
                $songwriter_data[0]['percentage'] = 100;

                // if there is multiple songwriters delete
                if(count($song->writers) > 1){
                    foreach ($song->writers as $w) {
                        if($w->id != $songwriter_data[0]['type_id']) {
                            $w->songs()->detach($song->id);
                        }
                    }
                }
            }

            // get the songwriter data... and check it first
            foreach($songwriter_data as &$s) {

                // change type
                if($s['type'] == 'songwriter') {
                    $s['type'] = 'user';
                }

                // check if model exists
                if(!$model = ('App\\'.ucfirst($s['type']))::find($s['type_id'])) {
                    return response()->json([
                        'message' => 'User/Team does not exist'
                    ], Response::HTTP_UNAUTHORIZED);
                }     

                // except for me, check if this songwriter is apart of my account or team account
                if($s['type'] == 'user' && ($request->user()->id != $s['type_id'])) {

                    if(!$request->user()->hasRole('superadmin|admin') && !$model->usersIBelongTo()->where('id', $request->user()->id)->exists()) {
                        return response()->json([
                            'message' => 'This songwriter is not apart of this account'
                        ], Response::HTTP_UNAUTHORIZED);
                    }
                    
                } 
            }

            // percent must be correct
            if(array_sum(array_column($songwriter_data, 'percentage')) != 100) {
                return response()->json([
                    'message' => 'Total percentage for all parties must equal 100 percent'
                ], Response::HTTP_BAD_REQUEST);
            }   
            
            // since everything is good now... lets start updating/creating values
            foreach($songwriter_data as $s2) {

                // set the model
                $model_id = $s2['type_id'];
                $model = ('App\\'.ucfirst($s2['type']))::find($model_id);

                // unset to prevent update
                unset($s2['type']);
                unset($s2['type_id']);

                // reset the signed info
                $s2['signed_at'] = null;

                //update relationship 
                if($model->songs()->where('id', $song->id)->exists()) {
                    
                    $model->songs()->updateExistingPivot($song->id, $s2);

                    // Send email to all parties except me
                    if($request->get('no_email') == false && $request->user()->id != $model_id) {

                        // get the temp value information for these status 3 users
                        $model = $this->getSongwriterData($request, $model);

                        // send email
                        Notification::route('mail', $model['email'])
                                    ->notify(new \App\Notifications\SongAmended());

                    }

                // create relationship
                } else {

                    $model->songs()->attach($song->id, $s2);

                    // send email
                    if($request->get('no_email') == false) {
                        Notification::send($model, new \App\Notifications\NewSong($model, $song));
                    }
                }              
            }
        }

        // create the amendment record
        $amendment = \App\Amendments::create([
            'amendable_type' => 'App\Model\Song',
            'amendable_id' => $song->id,
            'old_data' => $amend_old_data,
        ]);

        // add amendment_id to request
        $request->merge([
            'amendment_id' => $amendment->id
        ]);

        // update the user info
        if($song->update($request->only(['title', 'amendment_id']))) {

            // get the latest info (will be more efficent later)
            $song = Song::find($song->id);
            $song->writers;
            
            // update the amendment
            $amendment->new_data = $song;
            $amendment->save();

            return response()->json($song);
        }
	}

	/**
     * Removes or Soft Deletes the record
     * @param  Request $request Request Object
     * @param  integer  $id      ID of the record 
     * @return Object
     */
	public function remove(Request $request, Song $song)
	{
		// delete song
		$song->delete();

		// return the response
		return response()->json([
			'message' => 'Song has been deleted.'
        ], Response::HTTP_BAD_REQUEST);
	}


    /**
     * Sign the document
     * @param  Request   $request   Request Object
     * @param  Song      $song      Song Object
     * @param  Signature $signature Signature Object
     * @return array                Message
     */ 
    public function signDocument(Request $request, Song $song, Signature $signature)
    {  
        // sign the document
        $request->user()->songs()->updateExistingPivot($song->id, [
            'signature_id' => $signature->id,
            'signed_at' => date('Y-m-d H:i:s')
        ]);

        // new audit record
        Audit::record('signed', $song);

        // for later use
        $all_signed = false;

        // check if everyone signed this document
        if(!User::whereHas('songs', function ($q) use ($song) {
            $q->where('id', $song->id);
            $q->where('signature_id', NULL);
        })->get()->first()){
            // mark all signed as true
            $all_signed = true;
        }

        // get all the users in this song
        foreach($song->writers as $writer) {

            $user = $this->getSongwriterData($request, $writer, false);

            // send an email to everyone but me that i signed the document
            if($writer->id != $request->user()->id) {
                Notification::send($user, new \App\Notifications\SongUserSigned($request->user(), $song, $user));
            }

            // if all signed, email everyone that the document has been signed.
            if($all_signed) {
                Notification::send($user, new \App\Notifications\SongFullySigned($request->user(), $song, $user));
            }

        }

        // success message
        $return_message = 'Document sign succesful';

        // mark that this has been fully signed
        if($all_signed) {

            // mark fully signed in the DB
            $song->fully_signed = 1;
            $song->save();
            
            // fire song completely signed event
            event(new SongCompletelySigned($song));

            // return message
            $return_message .= ". All Parties signed";
        }

        // return
        return response()->json([
            'message' => $return_message
        ], Response::HTTP_OK);
    }

    protected function getSongwriterData($request, $model, $array = true)
    {
        // get the temp value information for these status 3 users
        if($model->status == 3) {
            
            // return the type
            if(!is_null($team_id = $request->header('X-Team-Id'))) {
                $my_type = 'team';
                $my_type_id = $team_id;
            } else {
                $my_type = 'user';
                $my_type_id = $request->user()->id;

            }

            // get the temp value
            $temp_values = json_decode(DB::table($my_type.'ables')
                 ->select(DB::raw("temp_values"))
                 ->leftJoin($my_type.'s', $my_type.'s.id', '=', $my_type.'ables.'.$my_type.'able_id')
                 ->where($my_type.'_id', '=', $my_type_id)
                 ->where($my_type.'able_type', '=', 'App\\User')
                 ->where($my_type.'able_id', '=', $model->id)
                 ->first()
                 ->temp_values);

            // temp_value email has higher priority
            // and set the other information
            $model->email = $temp_values->email;
            $model->first_name = $temp_values->first_name;
            $model->last_name = $temp_values->last_name;
            $model->stage_name = $temp_values->stage_name;
            $model->inviter_type = $my_type;
            $model->inviter_id = $my_type_id;
        }

        if($array) {
            return $model->toArray();
        } else {
            return $model;
        }
    }


    protected function processSongwriters(&$songwriters)
    {
        if(!empty($songwriter)) {

            foreach($songwriter as $s) {

                // change type
                if($s['type'] == 'songwriter') {
                    $s['type'] = 'user';
                }

                // check if model exists
                if(!$model = ('App\\'.ucfirst($s['type']))::find($s['type_id'])) {
                    return response()->json([
                        'message' => 'User/Team does not exist'
                    ], Response::HTTP_UNAUTHORIZED);
                }    

                // by default
                $model->inviter_type = '';
                $model->inviter_id = '';

                // except for me, check if this songwriter is apart of my account or team account
                if(!$request->user()->hasRole('superadmin|admin') &&  $s['type'] == 'user' &&  $request->user()->id != $s['type_id']) {
                    
                    // default
                    $belongs = true;

                    if(!is_null($team_id = $request->header('X-Team-Id'))) {
                        $belongs = $model->teamsIBelongTo()->where('id', $team_id)->exists();
                    } else {
                        $belongs = $model->usersIBelongTo()->where('id', $request->user()->id)->exists();
                    }

                    if(!$belongs) {
                        return response()->json([
                            'message' => 'This songwriter is not apart of this account'
                        ], Response::HTTP_UNAUTHORIZED);
                    }
                }

                //cache type
                $type_id = $s['type_id'];

                // unset to prevent update
                unset($s['type']);
                unset($s['type_id']);

                //create relationship
                $model->songs()->attach($song->id, $s);

                // Send email to all parties except me
                if($request->get('no_email') == false && $request->user()->id != $type_id) {
                    
                    // get the temp value information for these status 3 users
                    $model = $this->getSongwriterData($request, $model);

                    // new song event
                    event(new SongCreated($model, $song));
                }
            }


            $writers = [];

            // append the songwriters relationship
            foreach($song->writers->toArray() as $w) {


                if($w['status'] == 3) {

                    if(is_null($creator_id = $request->header('X-Team-Id'))) {
                        $creator_id = $request->user()->id;
                        $type = 'user';
                    } else {
                        $type = 'team';
                    }

                    // get the temp_value of the user by the CREATOR TYPE
                    // im doing it this way because a user or a team acan add a user to the song
                    $user = DB::table($type.'ables')
                             ->select(DB::raw('temp_values'))
                             ->leftJoin($type.'s', $type.'s.id', '=', $type.'ables.'.$type.'able_id')
                             ->where($type.'_id', '=', $creator_id)
                             ->where($type.'able_type', '=', 'App\\User')
                             ->where($type.'able_id', '=', $w['id'])
                             ->first();

                    // all these checks and most importantly if the users status is 3
                    if(!is_null($user) && !is_null($user->temp_values)) {
                        $w = array_merge($w, json_decode($user->temp_values, true));
                    }
                }

                $writers[] = $w;
            }

            unset($song->writers);
            $song->writers = $writers;
        }
    }
}