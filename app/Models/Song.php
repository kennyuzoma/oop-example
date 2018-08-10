<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable;
use Nicolaslopezj\Searchable\SearchableTrait;

class Song extends Model implements Auditable
{
    use SoftDeletes, \OwenIt\Auditing\Auditable, SearchableTrait;

    /**
     * Default validation rules
     * @var array
     */
    public static $rules = [
        'title' => 'required',
    ];

    /**
     * Attributes that can be put in the database
     * @var array
     */
    protected $fillable = [
        'title',
        'locked',
        'amendment_id',
        'created_by'
    ];

    protected $casts = [
        'created_by' => 'array'
    ];

    protected $hidden = [
        'uuid'
    ];

    protected $appends = [
        'logo'
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
            'title' => 10
        ],

        'joins' => [
            'writers' => ['songs.id','posts.user_id'],
        ],
    ];

    public function getLogoAttribute()
    {
        if(!is_null($logo = \App\Model\User::find($this->created_by['user_id'])->settings['logo'])) {
            $logo = \Storage::url(config('app_settings.s3_logo_folder').'/'.$logo);
        } 
        
        return $this->attributes['logo'] = $logo;
    }

    public static function boot()
    {
        parent::boot();
        self::creating(function ($model) {
            $model->uuid = (string) \Uuid::generate();
        });
    }

    public function creator()
    {
        return User::find($this->created_by['user_id']);
    }

    /**
     * Users relationship
     * @return $this
     */
    public function users()
    {
        return $this->morphToMany('App\Model\User', 'userable')
                    ->withPivot('temp_values')
                    ->withTimestamps();
    }

    /**
     * Writers relationship
     * @return $this
     */
    public function writers()
    {
        return $this->morphedByMany('App\Model\User', 'songable')
                    ->as('split')
                    ->withPivot('owner_type', 'owner_description', 'percentage', 'signature_id', 'signed_at')
                    ->withTimestamps();
    }

}
