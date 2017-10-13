<?php

namespace App\Http\Controllers\Backend\Music;

use App\Models\Music\Track\Track;
use App\Http\Controllers\Controller;
use App\Repositories\Backend\Music\TrackRepository;
use App\Repositories\Backend\Music\ArtistRepository;
use App\Repositories\Backend\Music\GenreRepository;
use App\Http\Requests\Backend\Music\Track\ManageTrackRequest;
use App\Http\Requests\Backend\Music\Track\StoreTrackRequest;
use App\Http\Requests\Backend\Music\Track\UpdateTrackRequest;
use Illuminate\Validation\Rule;
use Download;
use Illuminate\Support\Facades\Cache;

class TracksController extends Controller
{
    protected $tracks;
    protected $artists;
    protected $genres;

    public function __construct(TrackRepository $tracks, ArtistRepository $artists, GenreRepository $genres)
    {
        $this->tracks = $tracks;
        $this->artists = $artists;
        $this->genres = $genres;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(ManageTrackRequest $request)
    {
        $title =  trans('labels.backend.music.tracks.all');
        $tracks = $this->tracks->query()
                ->whereNotNull('trackable_id')
                ->latest()
                ->paginate();

        return view('backend.music.tracks.index', compact('title', 'tracks'));
    }

    /**
     * Display the Track.
     *
     * @param  int  $track
     * @return \Illuminate\Http\Response
     */
    public function show($category, $genre, $trackableType, $trackableSlug, 
                        Track $track, ManageTrackRequest $request)
    {
        $title = trans('labels.backend.music.tracks.management');
        $artists = $this->artists->query()->pluck('name', 'name');
        $genres = $this->genres->query()->pluck('name', 'name');

        return view('backend.music.tracks.show', compact('title', 'track', 'artists', 'genres'));
    }

    public function clearCache(Track $track, ManageTrackRequest $request)
    {
        $key = "tracks/{$track->slug}";
        Cache::forget($key);

        return back()->withFlashSuccess("Track Cache Successfully  Refreshed");
    }

    public function download(Track $track, ManageTrackRequest $request)
    {
        $filename = $track->full_title . '.' . $track->file->getExtensionAttribute();
        // $download = Download::fromTrack($track);

        return response()->download($track->path, $filename);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Track $track, UpdateTrackRequest $request)
    {
        $result = $this->tracks->update($track, $request->only(
                                'title', 'comment', 'cover', 'main', 'features', 'producer', 
                                'year', 'number', 'genres', 'copyright'));

        // dd($result);

        return redirect()->route('admin.music.tracks.show', [$track->trackable->category, 
            $track->trackable->genre, $track->trackable_type, $track->trackable, $track])
                            ->withFlashSuccess(trans('alerts.backend.music.tracks.updated'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Track $track, ManageTrackRequest $request)
    {
        $this->tracks->delete($track);

        return back()->withFlashSuccess(trans('alerts.backend.music.tracks.deleted'));
    }
}
