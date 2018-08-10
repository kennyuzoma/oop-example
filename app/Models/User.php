<?php

namespace App;

use Illuminate\Notifications\Notifiable;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Passport\HasApiTokens;
use Laratrust\Traits\LaratrustUserTrait;
use Laravel\Cashier\Billable;
use OwenIt\Auditing\Contracts\Auditable;
use Nicolaslopezj\Searchable\SearchableTrait;

class User extends Authenticatable implements Auditable
{
    use LaratrustUserTrait, Billable, SoftDeletes, HasApiTokens, Notifiable, SearchableTrait;
    use \OwenIt\Auditing\Auditable;
    /**
     * Default validation rules
     * @var array
     */
    public static $rules = [
        'email' => 'required|email|unique:users,email',
        //'password' => 'required',
        //'password_conf' => 'required|same:password',
        'first_name' => '',
        'last_name' => '',
        'middle_name' => '',
        'stage_name' => '',
        'pro_id' => '',
        'ipi_cae' => '',
        'address' => '',
        'address2' => '',
        'city' => '',
        'state' => '',
        'zip' => '',
        'phone' => ''
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'first_name',
        'last_name',
        'middle_name',
        'stage_name',
        'email',
        'password',
        'pro_id',
        'publisher_id',
        'ipi_cae',
        'address',
        'address2',
        'city',
        'state',
        'zip',
        'phone',
        'status',
        'settings',
        'created_by',
        'code'
    ];

    /**
     * Attributes that are hidden from the object
     * @var array
     */
    protected $hidden = [
        'password',
        'remember_token',
        'publisher_id',
        'pro_id',
        'stripe_id',
        'card_brand',
        'card_last_four',
        'trial_ends_at',
        'active_plan',
        'settings',
        'created_by',
        'uuid',
        'code'
    ];
    

    /**
     * Different user statuses and what they mean
     * @var [type]
     */
    protected $user_statuses = [
        'active'                => 1,
        'confirm'               => 2,
        'songwriter_created'    => 3,
        'deactivated'           => 4,
    ];


    /**
     * Attributes that are appended to the object
     * @var [array
     */
    protected $appends = ['full_name', 'publisher', 'pro', 'signature'];

    /**
     * Attributes that are casted
     * @var [type]
     */
    protected $casts = [
        'settings' => 'array',
        'created_by' => 'array'
    ];

    /**
     * Searchable rules.
     *
     * @var array
     */
    protected $searchable = [
        /**
         * Columns and their priority in search results.
         * Columns with higher values are more important.
         * Columns with equal values have equal importance.
         *
         * @var array
         */
        'columns' => [
            'first_name' => 10,
            'last_name' => 10,
            'middle_name' => 10,
            'stage_name' => 10,
            'address' => 10,
            'address2' => 8,
            'city' => 8,
            'state' => 8,
            'zip' => 8,
            'phone' => 8,
            'code' => 8,
            'email' => 10
        ],

        'joins' => [
            //'posts' => ['users.id','posts.user_id'],
        ],
    ];


    public static function boot()
    {
        parent::boot();

        //always create a uuid when creating
        self::creating(function ($model) {
            $model->uuid = (string) \Uuid::generate();
        });
    }

    /**
     * Accessor - PRO
     * @param  string $value 
     * @return string        Pro Name
     */
    public function getProAttribute($value)
    {  

        if(!isset($this->attributes['pro_id']) || is_null($this->attributes['pro_id'])) {
            return null;
        }

        return Pro::find($this->attributes['pro_id'])->name;
    }

    /**
     * Accessor - Signature attribute
     * @param  string $value 
     * @return object
     */
    public function getSignatureAttribute($value)
    {  

        if(!isset($this->attributes['signature_id']) || is_null($this->attributes['signature_id'])) {
            return null;
        }

        return Signature::find($this->attributes['signature_id']);
    }
    
    /**
     * Accessor - Full name attribute
     * @param  string $value 
     * @return $this       
     */
    public function getFullNameAttribute($value)
    {
        $full_name = null;

        if($this->first_name != '') {
            $full_name = $this->first_name . ' ';
        }

        if($this->middle_name != '') {
            $full_name .= $this->middle_name . ' ';
        }

        if($this->last_name != '') {
            $full_name .= $this->last_name;
        }

        if(!is_null($full_name)) {
            return $this->attributes['full_name'] = $full_name;
        } else {
            return $this->attributes['full_name'] = null;
        }
    }

    /**
     * Mutator - Set the password to a hash
     * @param string $pass
     * @return  object
     */
    public function setPasswordAttribute($pass)
    {
        $this->attributes['password'] = Hash::make($pass);
    }

    /**
     * Accessor - Publisher attribute
     * @return $this 
     */
    public function getPublisherAttribute()
    {
        return $this->attributes['publisher_id'] = \App\Publisher::find($this->publisher_id);    
    }

    /**
     * Teams relationship
     * @return $this
     */
    public function teams()
    {
        return $this->morphToMany('App\Team', 'teamable')
                    ->withPivot('temp_values')
                    ->withTimestamps();
    }

    /**
     * Songs relationship
     * @return $this
     */
    public function songs()
    {
        return $this->morphToMany('App\Model\Song', 'songable')
                    ->withPivot('owner_type','owner_description','percentage','signature_id')
                    ->withTimestamps();
    }

    /**
     * Users relationship
     * @return $this
     */
    public function users()
    {
        return $this->morphedByMany('App\Model\User', 'userable')
                    ->withPivot('temp_values')
                    ->withTimestamps();
    }

    /**
     * Writers relationship
     * @return $this
     */
    public function usersIBelongTo()
    {
        return $this->morphToMany('App\Model\User', 'userable')
                    ->withPivot('temp_values')
                    ->withTimestamps();
    }


    /**
     * Signatures relationship
     * @return $this
     */
    public function signatures()
    {
        return $this->hasMany('App\Model\Signature');
    }

}