<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class PasswordReset extends Model
{
    public static $rules = [
        'email' => 'required|email|exists:users,email'
    ];

    protected $fillable = [
        'email',
        'token'
    ];

    protected $table = 'password_resets';
}
