<?php

namespace App\Models\Music\Album;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia\HasMediaTrait;
use Spatie\MediaLibrary\HasMedia\Interfaces\HasMediaConversions;
use Laravel\Scout\Searchable;

class Album extends Model implements HasMediaConversions
{
    use AlbumAttribute,
    	AlbumRelationship,
    	AlbumScope,
    	SoftDeletes,
        HasMediaTrait;

    protected $fillable = ['artist_id', 'title', 'slug', 'description', 'status'];

    protected $dates = ['deleted_at'];

    /**
     * The relationships to always eager-load.
     *
     * @var array
     */
    protected $with = ['artists', 'media'];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::deleting(function ($album) {
            $album->tracks->each->delete();
        });
    }

    public function registerMediaConversions()
    {
        $this->addMediaConversion('thumb')
                ->performOnCollections('cover')
                ->width(75)
                ->height(75)
                ->sharpen(10)
                ->optimize();
    }

    public function attachTrack(Model $track)
	{
		return $this->tracks()->save($track) ? true : false;
	}
}
