<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Vinkla\Hashids\Facades\Hashids;
use App\Jobs\ProcessNewsletter;
use Notification;
use Validator;
use App\Model\User;
use App\Model\Signature;

class UserController extends Controller
{

    /**
     * Get all records
     * @return object
     */
	public function all(Request $request)
	{
		// get all the users
		return response()->json(User::paginate($request->get('limit')));
	}

    /**
     * Get a single record
     * @param  Request $request Request class
     * @param  integer  $id      User Integer
     * @return object
     */
	public function get(Request $request, User $user)
	{ 
        // return the songwriter
        return response()->json($user);
		
	}

    /**
     * Adds a record to the database
     * @param Request $request Request Object
     * @return object
     */
	public function add(Request $request)
	{
        //validation
        $validator = Validator::make($request->all(), [
            //'email' => $email_validation,
            'email' => 'required|email|unique:users,email',
            'password' => 'required|' . config('password.rules'),
            'password_conf' => 'required|same:password',
            'first_name' => 'required',
            'last_name' => 'required',
            'settings' => 'array'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors()
            ], Response::HTTP_BAD_REQUEST);
        }

        // handle settings
        foreach($default_user_settings = config('user_settings') as $k => $v) {
            $default_user_settings[$k] = $v['default'];
        }

        if(!is_null($settings = $request->get('settings'))) {

            // get the config user settings map
            $user_settings_map = config('user_settings');

            // by default
            $val_array = [];

            // check if setting exists
            foreach($settings as $key => $value) {

                if(!array_key_exists($key, $user_settings_map)) {
                    unset($settings[$key]);
                    continue;
                }

                // build validation array
                if(!is_null($rules = $user_settings_map[$key]['rules'])) {
                    $val_array[$key] = $rules;
                }

            }

            // check if setting passes validation
            $validator = Validator::make($settings, $val_array);

            if ($validator->fails()) {
                return response()->json([
                    'message' => $validator->errors()
                ], Response::HTTP_BAD_REQUEST);
            }

            $settings = array_merge($default_user_settings, $settings);
        } else {
            $settings = $default_user_settings;
        }

        // random code for user
        $code = str_random(10).str_random(10).str_random(15);

        $request->merge([
            'code' => $code,
            'settings' => $settings
        ]);

        // the created_by fields are filled in
        if (isset($request->user()->id)) {

            // created by array
            $created_by['user_id'] = $request->user()->id;

            // add a creator to the song
            if(!is_null($team_id = $request->header('X-Team-Id'))) {
                $created_by['team_id'] = (int) $team_id;
            } 

            // add to the request
            $request->merge(['created_by' => $created_by]);

        }

        // add to request
        $request->merge(['status' => 2]);

        // attempt to create the user
        if (!$user = User::create($request->except('role'))) {
            return response()->json([
                'message' => "There was an error creating the $type"
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        // check email settings
        $this->checkEmailSettings($user, $settings, true);
    

        // send notification
        Notification::send($user, new \App\Notifications\UserConfirmEmail([
            'name' => $user->first_name,
            'code' => $code
        ]));

        

        // attach user role
        $user->attachRole('user');

        if(config('app.env') == 'local') {

            $user = $user->toArray();
            $user['code'] = $code;
        }

        // return user object
		return response()->json($user);
	}

    

    /**
     * Edit record
     * @param  Request $request Request Object
     * @param  integer  $id      ID of the record
     * @return object
     */
	public function put(Request $request, User $user)
	{
        // if you arent an admin and this isnt your user
        if((!Auth::user()->hasRole('superadmin|admin')) && (Auth::user()->id != $user->id)) {
        	return response()->json([
        		'message' => 'You can not perform this action'
        	], Response::HTTP_UNAUTHORIZED);
        }

		//validation
		$validator = Validator::make($request->all(), [
            'email' => 'unique:users,email,'.$user->id,
            'pro_id' => 'exists:pros,id',
            'publisher_id' => 'exists:publishers,id',
            'signature_id' => '',
            'settings' => 'array'
        ]);

		if ($validator->fails()) {
            return response()->json([
            	'message' => $validator->errors()
            ], Response::HTTP_BAD_REQUEST);
        }

        // handle settings
        if(!is_null($settings = $request->get('settings'))) {

            $user_settings_map = config('user_settings');

            // by default
            $val_array = [];

            // check if setting exists
            foreach($settings as $key => $value) {

                if(!array_key_exists($key, $user_settings_map)) {
                    unset($settings[$key]);
                    continue;
                }

                // build validation array
                if(!is_null($rules = $user_settings_map[$key]['rules'])) {
                    $val_array[$key] = $rules;
                }

            }

            // check if setting passes validation
            $validator = Validator::make($settings, $val_array);

            if ($validator->fails()) {
                return response()->json([
                    'message' => $validator->errors()
                ], Response::HTTP_BAD_REQUEST);
            }

            // combine settings
            $settings = array_merge($user->settings, $settings);
            
            // throw is in the request array
            $request->merge([
                'settings' => $settings
            ]);
        } 

        // these fields can not be edited
        $non_editable = [
            'email',
            'password',
            'created_by',
            'code',
        ];

        // check if this is an email request change
        if(!is_null($email = $request->get('email')) && $email != $user->email) {
            $this->changeEmailRequest($user, $request->get('email'));
        }

        // admins and super admins can edit anything
        if($request->user()->hasRole('superadmin|admin')) {
            $non_editable = [];
        }
        
        // update the user info
        if($user->update($request->except($non_editable))) {

            // show the settings array
            if(!is_null($request->get('settings'))) {
                //$user = $user->toArray();
                $user->settings = $settings;

                // check email settings
                $this->checkEmailSettings($user, $settings);
            }

        	return response()->json($user);
        }
	}

    /**
     * Log user in
     * @param  Request $request Request Object
     * @return object
     */
	public function login(Request $request)
	{
		//validation
		$validator = Validator::make($request->all(),[
			'email' => 'required|email',
			'password' => 'required'
		]);

		if ($validator->fails()) {
            return response()->json([
            	'message' => $validator->errors()
            ], Response::HTTP_BAD_REQUEST);
        }

		if(!Auth::attempt([
			'email' => $request->get('email'), 
			'password' => $request->get('password')]))
		{
            return response()->json([
            	'message' => 'The email or password you entered is invalid.'
            ], Response::HTTP_BAD_REQUEST);
        }

        // get user object
        $user = Auth::user();

        // return the token
        $user['token'] = $user->createToken(config('app.token'))->accessToken;

        // return the user
        return $user;
	}

    /**
     * Log User Out
     * @param  Request $request Request Object
     * @return object
     */
	public function logout(Request $request)
	{
		if(!Auth::logout()) {
			return response()->json([
				'message' => 'There was a problem logging the user out'
			]);
		}

		return response()->json([
			'message' => 'User has been logged out'
		]);
	}

    /**
     * Removes or Soft Deletes the record
     * @param  Request $request Request Object
     * @param  integer  $id      ID of the record 
     * @return Object
     */
	public function remove(Request $request, User $user)
	{
		// remove all relationships?

		// delete user
		$user->delete();

		// return the response
		return response()->json([
			'message' => "$type has been deleted."
        ], Response::HTTP_BAD_REQUEST);
	}

    /**
     * Deactivate the User
     * @param  Request $request Object
     * @param  integer  $id      ID of the User
     * @return Object
     */
	public function deactivate(Request $request, User $user)
	{
		// update to deactivated status and save
		$user->status = 4;
		$user->save();

		// return response
		return response()->json([
        	'message' => 'User has been deactivated'
        ], Response::HTTP_BAD_REQUEST);
	}

    /**
     * Invite the User to the application
     * @param  Request $request Request Object
     * @return object
     */
	public function inviteUser(Request $request)
	{
		// check if email address is valid
		$validator = Validator::make($request->all(),[
			'email' => 'required|email|unique:users,email'
		]);

		// if validation fails
        if ($validator->fails()) {
            return response()->json([
            	'message' => $validator->errors()
            ], Response::HTTP_BAD_REQUEST);
        }

        // get the email
		$email = $request->get('email');

        // add invite record
        \App\Invite::create([
            'type' => 'invite',
            'inviter_id' => $request->user()->id,
            'invitee_email' => $email
        ]);

        // send notification
        Notification::send($user, new \App\Notifications\UserInvitedEmail([
            'email' => $email
        ]));

        return response()->json([
            'message' => 'Invite to ' . $email . ' sent.'
        ]);
            
	}



    /**
     * Change the user role
     * @param  Request $request Return Object
     * @param  Integer  $user_id User ID
     * @param  String  $role    The Role of the user
     * @return object
     */
	public function changeRole(Request $request, User $user, $role)
	{
		// check if role exists
		if(!\App\Model\Role::where('name', $role)->first()) {
			return response()->json([
				'message' => 'Role does not exist'
			], Response::HTTP_NOT_FOUND);
		}

		// get the old role
		$old_role = $user->roles->first()->name;

		// if the old role is not the same as the new requested role
		if($old_role != $role) {

			// if this is an admin trying to change the role to a super admin
			if($old_role == 'admin' && $role == 'superadmin') {
				return response()->json([
					'message' => 'Only super admins can assign superadmin roles.'
				], Response::HTTP_UNAUTHORIZED);
			}

            // do checks here for teem admin adding
            
            if($old_role == 'teamadmin' && ($role == 'superadmin' || $role == 'admin')) {
                return response()->json([
                    'message' => 'You can not assign this role to this user'
                ], Response::HTTP_UNAUTHORIZED);
            }

			// detatch and attach roles
			$user->detachRole($old_role);
			$user->attachRole($role);
		}

		// response
		return response()->json([
			'message' => 'User role saved'
		]);
	}

    public function changeLogo(Request $request, User $user)
    {
        //validation
        $validator = Validator::make($request->all(), [
            'image' => 'mimes:jpeg,png',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors()
            ], Response::HTTP_BAD_REQUEST);
        }  

        // set the logo folder
        $logo_folder = config('app_settings.s3_logo_folder');

        // by default
        $image = null;

        // if the request image is not blank
        if(!is_null($request->file('image'))) { 

            // attempt to upload the signature
            if(!$image = $request->file('image')->store($logo_folder)) {
                return response()->json([
                    'message' => "There was error saving the logo"
                ], Response::HTTP_INTERNAL_SERVER_ERROR);
            }

            $image = basename($image);
        }

        
        if(!is_null($user->settings['logo'])) {

            if(Storage::exists($raw_file = $logo_folder.'/'.$user->settings['logo'])) {
                // remove old logo, if any
                Storage::delete($raw_file);
            }
        }

        // change the setting
        $this->changeSetting($user, 'logo', $image);

        // return
        return response()->json([
            'message' => 'Logo succesfully changed'
        ]);
    }


    public function sendConfirmationEmail(Request $request, User $user)
    {
        try {
            // send notification
            Notification::send($user, new \App\Notifications\UserConfirmEmail([
                'name' => $user->first_name,
                'code' => sha1($user->id)
            ]));

            return response()->json([
                'message' => 'Confirmation email succesfully sent'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'There was a problem sending this email'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }


    }

    public function confirmUser(Request $request)
    {
        //validation
        $validator = Validator::make($request->all(), [
            'code' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors()
            ], Response::HTTP_BAD_REQUEST);
        }

        if(!$user = User::where('code', $request->get('code'))->first()) {
            return response()->json([
                'message' => 'Invalid Code'
            ], Response::HTTP_UNAUTHORIZED);
        }

        // change the user's status
        $user->status = 1;
        $user->save();

        try {

            Notification::send($user, new \App\Notifications\UserWelcomeEmail([
                'name' => $user->first_name,
                'code' => sha1($user->id)
            ]));

        } catch (\Exception $e) {

            return response()->json([
                'message' => 'Welcome email could not be sent.'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);

        }

        return response()->json([
            'message' => 'User confirmed',
            'token' => $user->createToken('Token')->accessToken,
            'user' => $user
        ]);

    }

    public function makeSignatureDefault(Request $request, User $user, Signature $signature)
    {
        // save the new default signature
        $user->signature_id = $signature->id;
        $user->save();

        // return
        return response()->json([
            'message' => 'New default signature set'
        ]);
    }

    public function changeEmailRequest($user, $target_email)
    {       
        // check if this record already exists
        $changed_email = ChangedEmail::where('email', $target_email)
                                ->where('user_id', $user->id) 
                                ->first();

        if(!$changed_email) {

            $changed_email = ChangedEmail::create([
                'user_id' => $user->id,
                'email' => $target_email
            ]);
        }

        // send email 
        Notification::route('mail', $target_email)->notify(new \App\Notifications\UserChangeEmailRequestEmail([
            $changed_email->toArray()
        ]));

    }

    public function changeEmail(Request $request)
    {
        //validation
        $validator = Validator::make($request->all(), [
            'email' => 'exists:changed_emails,email',
            'confirmation_code' => 'exists:changed_emails,confirmation_code'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors()
            ], Response::HTTP_BAD_REQUEST);
        }

        // get the changed email record
        $changed_email = ChangedEmail::where('email', $request->get('email'))
                                    ->where('confirmation_code', $request->get('confirmation_code')) 
                                    ->first();

        // if this has already been confirmed
        if($changed_email->confirmed == 1) {
            return response()->json([
                'message' => 'This email has already been confirmed.'
            ], Response::HTTP_UNAUTHORIZED);
        }

        try {

            // change email since everything went well
            $user = User::find($changed_email->user_id);
            $user->email = $changed_email->email;
            $user->save();

            // mark as confirmed
            $changed_email->confirmed = 1;
            $changed_email->save();

            // send mail that email was changed
            Notification::send($user, new \App\Notifications\UserChangedEmail($user->toArray()));

            return response()->json([
                'message' => 'Email change successful'
            ]);

        } catch(\Exception $e) {

            return response()->json([
                'message' => 'There was an issue changing the email'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
        
    }

    public function subscribeToNewsletter(Request $request, User $user)
    {
        ProcessNewsletter::dispatch($user, 'subscribe');

        return response()->json([
            'message' => 'User succesfully subscribed to newsletter.'
        ]);
    }

    public function unsubscribeFromNewsletter(Request $request, User $user)
    {
        ProcessNewsletter::dispatch($user, 'unsubscribe');

        return response()->json([
            'message' => 'User succesfully unsubscribed from newsletter.'
        ]);
    }

    public function getNotifications(Request $request, User $user)
    {
        // get default notifications
        $notifications = $user->notifications;
        
        if($request->get('type') == 'unread') {
            $notifications = $user->unreadNotifications;
        }

        return response()->json($notifications);
    }

    /***************************************************************************/

    private function checkEmailSettings($user, $settings, $new_account = false)
    {
        if($settings['email_notifications'] == 1) {
            ProcessNewsletter::dispatch($user, 'subscribe');
        } else {
            if(!$new_account) {
                ProcessNewsletter::dispatch($user, 'unsubscribe');
            }
        }
    }

    private function changeSetting($user, $setting_name, $new_value) 
    {
        $settings = $user->settings;
        $settings[$setting_name] = $new_value;
        // save the settings
        $user->settings = $settings;
        $user->save();
    }

}
