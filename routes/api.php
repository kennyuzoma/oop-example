<?php

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/
// under API
Route::prefix('/')->namespace('Api')->group(function () {
    
	// User (Public)
	Route::post('user/login', 'UserController@login');
	Route::post('user/register', 'UserController@add');
    Route::post('user/confirm/send/{user}', 'UserController@sendConfirmationEmail');
    Route::get('user/confirm', 'UserController@confirmUser');
	Route::post('user', 'UserController@add');
	Route::get('user/forgotPassword', 'UserController@forgotPassword');
	Route::get('user/resetPassword', 'UserController@resetPassword');


    // Reset Password
    Route::post('password/send_request', 'PasswordController@sendPasswordResetEmail');
    Route::post('password/reset', 'PasswordController@resetPassword');
    Route::put('password/{user}/change', 'PasswordController@changePassword');


	// Require Auth Bearer Token (logged in)
	Route::middleware(['auth:api','checkApiClientSecret'])->group(function () {


    	// User
		Route::get('user/invite', 'UserController@inviteUser');
        Route::put('user/{user}/add', 'UserController@addToUser')->middleware('can:update,user');
		Route::delete('user/{user}/remove', 'UserController@removeFromUser')->middleware('can:update,user');
		Route::get('user/logout', 'UserController@logout');
		Route::get('user/deactivate', 'UserController@deactivate')->middleware('can:delete,user');
		Route::get('user', 'UserController@all');
		Route::get('user/{user}', 'UserController@get');
		Route::put('user/{user}', 'UserController@put')->middleware('can:update,user');
		Route::delete('user/{user}', 'UserController@remove')->middleware('can:delete,user');
		Route::put('user/{user}/role/{role}', 'UserController@changeRole');//->middleware('role:superadmin|admin');
        Route::put('user/{user}/signature/{signature}/default', 'UserController@makeSignatureDefault')->middleware('can:view,signature');
        Route::post('user/{user}/change_email', 'UserController@changeEmail');
        Route::put('user/{user}/subscribe', 'UserController@subscribeToNewsletter');
        Route::put('user/{user}/unsubscribe', 'UserController@unsubscribeFromNewsletter');
        Route::post('user/{user}/changeLogo', 'UserController@changeLogo');
        Route::get('user/{user}/notifications', 'UserController@getNotifications');

		// Songs
		Route::get('song', 'SongController@all');
        Route::get('song/{song}', 'SongController@get');
        Route::get('song/{song}/split', 'SongController@getSplit');
		Route::post('song', 'SongController@add')->middleware('can:create,App\Model\Song');
		Route::put('song/{song}', 'SongController@put')->middleware('can:update,song');
		Route::delete('song/{song}', 'SongController@remove')->middleware('can:update,song');
		Route::get('song/{song}/lock', 'SongController@lockDocument')->middleware('can:update,song');
		Route::get('song/{song}/unlock', 'SongController@lockDocument')->middleware('can:update,song');
        Route::post('song/{song}/sign/{signature}', 'SongController@signDocument')->middleware('can:sign,song,signature');
        Route::get('song/{song}/audit', 'SongController@getAudit');

		// Signatures
		Route::get('signature', 'SignatureController@all')->middleware('role:superadmin');
		Route::get('signature/{signature}', 'SignatureController@get');//->middleware('can:view,signature');
		Route::post('signature', 'SignatureController@add')->middleware('can:create,App\Model\Signature');
		Route::delete('signature/{signature}', 'SignatureController@remove')->middleware('can:delete,signature');
        Route::put('signature/{signature}/set_default', 'SignatureController@setDefaultForUser');

		// Roles
		Route::get('role', 'RoleController@all')->middleware('role:superadmin');
		Route::get('role/{role}', 'RoleController@get')->middleware('can:view,role');
		Route::post('role', 'RoleController@add')->middleware('can:create,App\Model\Role');
		Route::put('role/{role}', 'RoleController@put')->middleware('can:update,role');
		Route::delete('role/{role}', 'RoleController@remove')->middleware('can:delete,role');

    
	});

});