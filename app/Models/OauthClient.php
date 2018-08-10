<?php
namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class OauthClient extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'name',
        'secret',
        'redirect',
        'personal_access',
        'password_client',
        'revoked'
    ];

}