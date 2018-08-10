<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Audit extends Model
{

    public $rules = [
        'name' => 'required',
        'file_name' => 'required',

    ];

    protected $fillable = [
        'user_type',
        'user_id',
        'event',
        'auditable_type',
        'auditable_id',
        'old_values',
        'new_values',
        'comments',
        'url',
        'ip_address',
        'user_agent'
    ];

}
