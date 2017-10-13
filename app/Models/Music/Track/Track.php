<?php

namespace App\Models\Music\Track;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia\HasMediaTrait;
use Spatie\MediaLibrary\HasMedia\Interfaces\HasMediaConversions;
use Laravel\Scout\Searchable;

class Track extends Model implements HasMediaConversions
{
    use TrackAttribute,
    	TrackRelationship,
    	TrackScope,
    	SoftDeletes,
        HasMediaTrait;

    protected $fillable = [
    			'title', 'slug', 'year', 'number', 
    			'comment', 'album', 'composer', 
    			'bitrate', 'duration', 'copyright'
    ];

    protected $dates = ['deleted_at'];

    protected $with = ['artists', 'trackable.category', 'trackable.genre', 'media'];

    protected $appends = ['url', 'full_title'];

    public function registerMediaConversions()
    {
        $this->addMediaConversion('thumb')
                ->performOnCollections('cover')
                ->width(75)
                ->height(75)
                ->sharpen(10)
                ->optimize();
    }
}
