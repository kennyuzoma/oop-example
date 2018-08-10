<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

use Vinkla\Hashids\Facades\Hashids;
use OwenIt\Auditing\Contracts\Auditable;


class Signature extends Model implements Auditable
{
    use \OwenIt\Auditing\Auditable;

    public function getRouteKeyName()
    {
        return 'uuid';
    }

    /**
     * Validaiton rules
     * @var array
     */ 
    public static $rules = [
        'image' => ''
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'type',
        'user_id',
        'image',
        'font_id',
        'text',
        'hash'
    ];

    protected $hidden = [
        'id'
    ];

    /**
     * Attributes to append to the object
     * @var array
     */
    protected $appends = ['image_url'];

    public static function boot()
    {
        parent::boot();
        self::creating(function ($model) {
            $model->uuid = (string) \Uuid::generate();
        });
    }

    /**
     * User relationship
     * @return $this
     */
    public function user()
    {
        return $this->belongsTo('App\Model\User');
    }

    /**
     * Accessor - Image URL
     * @return URL Storage Facade
     */
    public function getImageUrlAttribute()
    {
        return Storage::url('signatures/'.$this->attributes['image']);
    }

/*
    public function setIdAttribute($value)
    {
        $this->attributes['id'] = Hashids::decode($value);
    }

    public function getIdAttribute()
    {
        return Hashids::encode($this->attributes['id']);
    }
*/
}
