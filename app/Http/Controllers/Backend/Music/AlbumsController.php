<?php

namespace App\Http\Controllers\Backend\Music;

use App\Http\Controllers\Controller;
use App\Models\Music\Category\Category;
use App\Models\Music\Genre\Genre;
use App\Models\Music\Album\Album;
use App\Repositories\Backend\Music\AlbumRepository;
use App\Repositories\Backend\Music\ArtistRepository;
use App\Repositories\Backend\Music\CategoryRepository;
use App\Repositories\Backend\Music\GenreRepository;
use App\Http\Requests\Backend\Music\Album\ManageAlbumRequest;
use App\Http\Requests\Backend\Music\Album\StoreAlbumRequest;
use App\Http\Requests\Backend\Music\Album\UpdateAlbumRequest;
use App\Http\Requests\Backend\Music\Album\UploadTracksRequest;
use Download;
use Illuminate\Support\Facades\Cache;

class AlbumsController extends Controller
{
    protected $albums;
    protected $artists;
    protected $categories;
    protected $genres;

    public function __construct(AlbumRepository $albums, CategoryRepository $categories,
                    GenreRepository  $genres, ArtistRepository $artists)
    {
        $this->albums = $albums;
        $this->artists = $artists;
        $this->categories = $categories;
        $this->genres = $genres;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(ManageAlbumRequest $request)
    {
        $title = trans('labels.backend.music.albums.all');
        $albums = $this->albums->query()->with('artists', 'category', 'genre')
                            ->withCount('tracks')->latest()->paginate();

        if (request()->wantsJson()) {
            return $albums;
        }

        return view('backend.music.albums.index', compact('title', 'albums'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreAlbumRequest $request)
    {     
        $album = $this->albums->create($request->only(
                    'artists', 'title', 'description', 
                    'category', 'cover', 'genre', 'type'
                ));

        if (request()->wantsJson()) {
            return response($album, 201);
        }

        return redirect()->route('admin.music.albums.show', [$album->category, $album->genre, $album])
                ->withFlashSuccess('Album Created Successfully');
    }

    public function storeCover(ManageAlbumRequest $request)
    {
        $this->validate($request, [
            'albumId' => 'required|integer|exists:albums,id',
            'cover' => 'required|image'
        ]);

        $this->albums->uploadCover($request->only('albumId', 'cover'));
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($category, $genre, Album $album, ManageAlbumRequest $request)
    {
        // Cache::flush();
        $tracks = $album->tracks()->latest()->paginate();
        $categories = $this->categories->getAll();
        $genres = $this->genres->getAll();

        return view('backend.music.albums.show', compact('album', 'tracks', 'categories', 'genres'));
    }

    public function clearCache(Album $album, ManageAlbumRequest $request)
    {
        $key = "albums/{$album->slug}";
        Cache::forget($key);

        return back()->withFlashSuccess("Album Cache Successfully  Cleared");
    }

    public function upload()
    {
        $this->validate(request(), [
            'file' => 'required|file',
            'album_id' => 'required|exists:albums,id',
        ]);

        $result = $this->albums->uploadTrack(request(['file', 'album_id']));

        return response($result['message'], $result['code']);
    }

    public function storeZip()
    {
        $this->validate(request(), [
            'file' => 'required|file',
            'category' => 'required|string',
            'genre' => 'required|string'
        ]);

        $album = $this->albums->uploadZip(request(['file', 'category', 'genre']));

        if ($album) {
            return redirect()->route('admin.music.albums.show', [$album->category, $album->genre, $album])
                ->withFlashSuccess('Album Created Successfully');
        }
        return back()->withFlashDanger('Whoops, SOmething Went Wrong... Album Could Not Be Uploaded');
    }

    public function generateArchive(Album $album, ManageAlbumRequest $request)
    {
        $result = $this->albums->createZip($album);

        if ($result) {
            return redirect()
                    ->route('admin.music.albums.show', [$album->category, $album->genre, $album])
                    ->withFlashSuccess("New Zip Archive Successfully Generated for {$album->full_title}");
        }
        return back()->withFlashDanger("Zip Archive Could Not Be Generated for {$album->full_title}");
    }   

    public function download(Album $album, ManageAlbumRequest $requests)
    {
        $zip = Download::from($album);

        return response()->download($zip);
    }

    public function edit(Album $album, ManageAlbumRequest $request)
    {
        $title = trans('labels.backend.music.albums.edit');
        $categories = $this->categories->getAll();
        $genres = $this->genres->getAll();

        return view('backend.music.albums.edit', compact('title', 'album', 'categories', 'genres'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Album $album, UpdateAlbumRequest $request)
    {
        $this->albums->update($album, $request->only(
                                        'artists', 'title','description', 
                                        'category', 'genre', 'type'
                                    )
        );

        return redirect()->route('admin.music.albums.show', [$album->category, $album->genre, $album])
                ->withFlashSuccess('Album Updated Successfully');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Album $album, ManageAlbumRequest $requests)
    {
        $this->albums->delete($album);

        return redirect()->route('admin.music.albums.index')
                ->withFlashSuccess('Album Deleted Successfully');
    }
}