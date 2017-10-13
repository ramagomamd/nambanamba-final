<?php

namespace App\Http\Controllers\Frontend\Music;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Repositories\Backend\Music\AlbumRepository;
use App\Repositories\Backend\Music\CategoryRepository;
use App\Models\Music\Album\Album;
use Illuminate\Support\Facades\Cache;
use SEOMeta;
use OpenGraph;
use Twitter;

class AlbumsController extends Controller
{
    protected $albums;
    protected $categories;

    public function __construct(AlbumRepository $albums, CategoryRepository $categories)
    {
        $this->albums = $albums;
        $this->categories = $categories;
    }
    
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        // Albums Index
        $title = 'All South African and International MP3 Albums Downloads';

        $albums = $this->albums->query()
                ->with('artists', 'category', 'genre')
                ->withCount('tracks')
                ->latest()
                ->paginate(10);
                
        $description = 'Stream and Download Full South African and International MP3 Music Albums. Download Album Songs Individually or Download a Full Zipped Album Free at NambaNamba.COM';
        $url = route('frontend.music.albums.index');

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

        return view('frontend.music.albums.index', compact('title', 'albums'));
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($category, $genre, $album)
    {
        $album = Cache::rememberForever("albums/{$album}", function () use ($album) {
            $album = Album::where('slug', $album)->first();
            $album = $album->load('tracks', 'category', 'genre');
            $index = 0;
            foreach ($album->tracks as $track) {
                $track->index = $index;
                $index++;
            }
            return $album;
        });

        $title = "Stream and Download {$album->full_title}";
        $url = route('frontend.music.albums.show', [$album->category, $album->genre, $album]);
        $cover = $album->cover ? $album->cover->getFullUrl() : '';

        // SEO Tags
        SEOMeta::setTitle($title);
        SEOMeta::setDescription($album->description);
        SEOMeta::addMeta('music.album:published_time', $album->created_at->toW3CString(), 'property');
        SEOMeta::addMeta('music.album:section', $album->category->name, 'property');
        SEOMeta::addKeyword(["Free {$album->category->name} {$album->genre->name} albums downloads", "download {$album->full_title} zipped", "stream all songs from {$album->full_title} free"]);

        OpenGraph::setDescription($album->description);
        OpenGraph::setTitle($title);
        OpenGraph::setUrl($url);
        OpenGraph::addProperty('type', 'music.album');
        OpenGraph::addProperty('locale', 'en-za');

        OpenGraph::addImage($cover);

        return view('frontend.music.albums.show', compact('title', 'album'));
    }
}
