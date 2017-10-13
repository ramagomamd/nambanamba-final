<?php

namespace App\Models\Music\Single;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Scout\Searchable;

class Single extends Model
{
    use SingleAttribute,
    	SingleRelationship,
    	SingleScope,
    	SoftDeletes;

    protected $fillable = ['status'];

    protected $with = ['artists'];

    protected $dates = ['deleted_at'];

    public function attachTrack(Model $track)
	{
		$track = $this->track()->save($track);
        if ($track) {
            $this->artists()->sync($track->all_artists);
            return true;
        }
        return false;
	}

    public function hasMedia($cover)
    {
        return false;
    }
}
