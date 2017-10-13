<?php

namespace App\Http\Controllers\Frontend\Music;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Repositories\Backend\Music\TrackRepository;
use App\Models\Music\Track\Track;
use Illuminate\Support\Facades\Cache;
use SEOMeta;
use OpenGraph;
use Twitter;

class TracksController extends Controller
{
    protected $tracks;

    public function __construct(TrackRepository $tracks)
    {
        $this->tracks = $tracks;
    }
    
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $title = 'All Tracks';

        $tracks = $this->tracks->query()
                ->whereNotNull('trackable_id')
                ->latest()
                ->paginate(20);

        $index = 0;
        foreach ($tracks as $track) {
            $track->index = $index;
            $index++;
        }

        if (request()->wantsJson()) {
            return $tracks;
        }

        $description = 'Stream and Download All South African and International MP3 Songs. Download and Stream All Songs Free at NambaNamba.COM';
        $url = route('frontend.music.tracks.index');

        // SEO Tags
        SEOMeta::setTitle($title);
        SEOMeta::setDescription($description);
        SEOMeta::setCanonical($url);

        OpenGraph::setDescription($description);
        OpenGraph::setTitle($title);
        OpenGraph::setUrl($url);
        OpenGraph::addProperty('type', 'music.albums');

        Twitter::setTitle($title);
        Twitter::setSite('@NambaNamba_Downloads');

        return view('frontend.music.tracks.index', compact('title', 'tracks'));
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($category, $genre, $trackableType, $trackableSlug, $track)
    {
        $track = Cache::rememberForever("tracks/{$track}", function () use ($track) {
            $track = Track::where('slug', $track)->first();
            $track->index = 0;
            return $track;
        });

        $title = "Stream and Download {$track->full_title} " . strtoupper($track->file->extension);
        $url = route('frontend.music.tracks.show', [$track->trackable->category, 
            $track->trackable->genre, $track->trackable_type, $track->trackable, $track]);
        $cover = $track->cover ? $track->cover->getFullUrl() : '';

        // SEO Tags
        SEOMeta::setTitle($title);
        SEOMeta::setDescription($track->description);
        SEOMeta::addMeta('music.album:published_time', $track->created_at->toW3CString(), 'property');
        SEOMeta::addMeta('music.album:section', $track->trackable->category->name, 'property');
        SEOMeta::addKeyword(["Free {$track->trackable->category->name} {$track->trackable->genre->name} songs downloads and streaming", "download or stream {$track->full_title}", "stream {$track->full_title} free at NambaNamba.COM"]);

        OpenGraph::setDescription($track->description);
        OpenGraph::setTitle($title);
        OpenGraph::setUrl($url);
        OpenGraph::addProperty('type', 'music.song');
        OpenGraph::addProperty('locale', 'en-za');

        OpenGraph::addImage($cover);

        return view('frontend.music.tracks.show', compact('title', 'track'));
    }
}
