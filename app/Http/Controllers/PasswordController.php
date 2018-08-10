<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use \Illuminate\Support\Facades\Route;
use App\Model\User;
use App\Model\PasswordReset;
use Validator;
use Illuminate\Support\Facades\Mail;
use App\Mail\ResetPasswordMail;
use App\Mail\UserChangedPassword;

class PasswordController extends Controller
{
    public function sendPasswordResetEmail(Request $request)
    {   
        // validation
        $validator = Validator::make($request->all(), PasswordReset::$rules);

        // if validation fails
        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors()
            ], Response::HTTP_BAD_REQUEST);
        }

        // get the email
        $email = $request->get('email');

        // create the token
        $token = md5($email);

        // check if there is a request already in the DB
        if(!$password_reset = PasswordReset::where('email', $email)->first()) {
            // create the password reset
            $password_reset = PasswordReset::create([
                'email' => $email,
                'token' => $token
            ]);
        }

        // send the email
        Notification::route('mail', $email)
                    ->notify(new \App\Notifications\UserResetPasswordEmail($password_reset));

        // return the response
        return response()->json($password_reset);
    }


    public function resetPassword(Request $request)
    {   
        // validation
        $validator = Validator::make($request->all(), [
            'token' => 'required|exists:password_resets,token',
            'password' => 'required|confirmed|' . config('password.rules'),
            'password_confirmation' => 'required'
        ]);

        // if validation fails
        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors()
            ], Response::HTTP_BAD_REQUEST);
        }

        // check if token is valid
        if(!$pw_record = PasswordReset::where('token', $request->get('token'))->first()) {
            return response()->json([
                'message' => 'Token is invalid.'
            ], Response::HTTP_BAD_REQUEST);
        } 

        // reset the password
        $user = User::where('email', $pw_record->email)->first();
        $user->password = $request->get('password');
        $user->update();

        // Delete the reset token
        $pw_record->delete();

        // send notification
        Notification::send($user, new \App\Notifications\UserChangedPasswordEmail($user->toArray()));

        return response()->json([
            'message' => 'Password successfully reset'
        ]);
    }

    public function changePassword(Request $request, User $user)
    {
        // validation
        $validator = Validator::make($request->all(), [
            'password' => 'required|confirmed|' . config('password.rules'),
            'password_confirmation' => 'required'
        ]);

        // if validation fails
        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors()
            ], Response::HTTP_BAD_REQUEST);
        }

        // reset the password
        $user->password = $request->get('password');
        $user->update();

        // send the email
        Notification::send($user, new \App\Notifications\UserChangedPasswordEmail($user->toArray()));

        return response()->json([
            'message' => 'Password successfully changed'
        ]);
    }
}