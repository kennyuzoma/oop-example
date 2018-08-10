<?php

namespace App;

use Laratrust\Models\LaratrustRole;
use Illuminate\Database\Eloquent\SoftDeletes;

class Role extends LaratrustRole
{
    use SoftDeletes;
    /**
     * Default validation rules
     * @var array
     */
    public static $rules = [
        'name' => 'required',
        'display_name' => 'required',
        'description' => ''
    ];

    /**
     * Attributes that can be fillable
     * @var array
     */
    protected $fillable = [
        'name',
        'display_name',
        'description'
    ];
}
