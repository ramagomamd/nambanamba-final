<?php

namespace App\Models\Music\Artist;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

trait ArtistScope
{
	public function scopeSimilarSlug(Builder $query, $slug)
	{
		return $query->where('slug', $slug);
	}

	public function scopeContributedTracks(Builder $query)
	{
		// Artist Tracks where pivot role is main
		dd($artist->tracks);
		return $query->whereHas('artists', function ($query) use ($artist) {
			$query->where('artist_id', $artist->id);
		});
		/*$artists = $tracks->artists->reject(function($artist) {
            return $artist->pivot->role != 'main';
        });*/
	}

}