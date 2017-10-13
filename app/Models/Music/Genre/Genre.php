<?php

namespace App\Models\Music\Genre;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Plank\Mediable\Mediable;
use Laravel\Scout\Searchable;

class Genre extends Model
{
    use GenreAttribute,
    	GenreRelationship,
    	GenreScope,
    	SoftDeletes,
        Mediable;

    protected $fillable = ['name', 'slug', 'description'];

    protected $dates = ['deleted_at'];
}
