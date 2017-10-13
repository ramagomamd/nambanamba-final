<?php 

namespace App\Repositories\Backend\Music;

use App\Repositories\BaseRepository;
use App\Models\Music\Album\Album;
use App\Events\Backend\Music\Album\AlbumCreated;
use App\Events\Backend\Music\Album\AlbumUpdated;
use App\Events\Backend\Music\Album\AlbumDeleted;
use App\Exceptions\GeneralException;
use Symfony\Component\HttpFoundation\File\File;
use App\Helpers\Validators\Mimes;
use SplFileInfo;
use Download;
use Spatie\Image\Image;
use Spatie\ImageOptimizer\OptimizerChainFactory;
use App\Models\Music\Artist\Artist;
use Illuminate\Http\UploadedFile;


class AlbumRepository extends BaseRepository
{
	const MODEL = Album::class;

	protected $tracks;
	protected $artists;
	protected $genres;
	protected $validate;
	protected $categories;

	public function __construct(TrackRepository $tracks, ArtistRepository $artists, GenreRepository $genres, 
								Mimes $validate, CategoryRepository $categories)
	{
		$this->tracks = $tracks;
		$this->artists = $artists;
		$this->genres = $genres;
		$this->validate = $validate;
		$this->categories = $categories;
	}

	public function create(array $input)
	{
		$album = $this->createAlbumStub($input);

		$this->attachCategoryAndGenre($album, $input);

		if ($album->save()) {
			// Create Artist Entity or Fetch One
			$this->attachArtists($album, $input['artists']);
			// Set Album Slug 
			$this->setSlug($album);

			event(new AlbumCreated($album));

			return $album;
		}
	}

	public function update(Album $album, array $input)
	{
		// Create Artist Entity or Fetch One
		$album->artists()->detach();
		$this->attachArtists($album, $input['artists']);

		$album->title = $input['title'];
		$album->description = $input['description'];
		$album->type = $input['type'];

		// Detach Previous Category
		$album->category()->dissociate();
		// Detach Previous Genres
		$album->genre()->dissociate();
		// Create and Attach Genres & Category
		$this->attachCategoryAndGenre($album, $input)->save();
		// Set Album Slug 
		$this->setSlug($album);

		event(new AlbumUpdated($album));

		if ($album->tracks->count() > 1) {
			$tracks = $album->tracks;
			$tracks->each(function($track) {
				$this->tracks->setFileTags($track);
			});
		}

		return $album;
	}

	public function attachArtists(Album $album, $artists)
	{
		$artists = str_ireplace(['&', 'and', 'vs'], ',', $artists);
		// Create Artist Entity or Fetch One
		if (isset($artists) && !is_null($artists)) {
			$artists = explode(',', $artists);
			foreach ($artists as $artist) {
				$artist = $this->artists->createArtistStub($artist);
				$album->artists()->attach($artist);
			}
		} else {
			$album->artists()->attach(Artist::UNKNOWN_ID);
		}
	}

	public function attachCategoryAndGenre(Album $album, array $data)
	{
		// Attach Category
		if (!empty($data['category'])) {
			// Sync Main Category
			$category = $this->categories->createCategoryStub($data['category']);
			$album->category()->associate($category);
		} else {
			$album->forceDelete();
			throw new GeneralException('Failed to create category for the album');
		}

		// Create and Attach Genres
		if (isset($data['genre']) && !is_null($data['genre'])) {
			$genre = $this->genres->createGenreStub($data['genre']);
			$album->genre()->associate($genre);
		} else {
			$album->forceDelete();
			throw new GeneralException('Failed to create genre for the album');
		}

		return $album;
	}

	public function setSlug(Album $album)
	{
		$slug = str_slug("{$album->fresh()->full_title}");
		if ($slug != $album->slug && $this->query()->similarSlug($slug)->exists()) {
            $count = 1;
            while ($this->query()->similarSlug(
            		$slug = str_slug("{$album->full_title}-{$count}"))->exists()) {
            	$count++;
            }
        }
        $album->update(['slug' => $slug]);
	}

	public function uploadTrack(array $data)
	{
		$file = $data['file'];
		$album = $this->query()->findOrFail($data['album_id']);

		dd($album);

		if ($file->isValid() && $album) {
			$track = $this->tracks->create($file, $album);

			// $track = $this->tracks->create($tags, $file_path, $album);
			if (!$track) {
				return [
					'message' => 'Failed to save track to database',
					'code' => 508
				];
			} else {
				return [
					'message' => 'Successfully Uploaded File to server',
					'code' => 201
				];
			}		
		} else {
			return [
				'message' => 'Failed to upload track, either track is invalid or corrupt',
				'code' => 508
			];
		}
	}

	public function uploadZip(array $data)
	{
		$file = $data['file'];
		if ($file->isValid()) {
			$filename = $file->getClientOriginalName();
			$filename = str_ireplace('–', '-', $filename);
			if (str_contains($filename, '-')) {
				// Extract Extension from title
				$p= strrpos($filename, '.');
				$full_title = substr($filename, 0, $p);
				// Find The Positions of the artists and featured artists
				$position = stripos($full_title, ' - ');
				$data['artists'] = substr($full_title, 0, $position);
				$data['title'] = substr($full_title, $position + 3);
				$data['description'] = '';
				$data['type'] = 'album';
				$album = $this->createAlbumStub($data);
				$this->attachCategoryAndGenre($album, $data)->save();
				$this->attachArtists($album, $data['artists']);
				// Set Album Slug 
				$this->setSlug($album);
				event(new AlbumCreated($album));
				$files = (new ExtractArchiveRepository($file->getRealPath()))->extract();
				$files = collect($files)->map(function ($file) {
					return (new UploadedFile($file->getRealPath(), true));
				});

				$covers = $files->filter(function ($file) {
					return $this->validate->image($file);
				});
				if (isset($covers)) {
					$bigCover = $covers->max(function ($cover) {
						return $cover->getSize();
					});
					$cover = $covers->filter(function ($cover) use ($bigCover) {
						return $cover->getSize() === $bigCover;
					})->shift();
					// dd($covers);
					if (isset($cover)) {
						$this->uploadCover(['cover' => $cover, 'albumId' => $album->id]);
					} 
				} 
				$files->each(function ($file) use ($album) {
					if ($file && $this->validate->audio($file)) {
						$track = $this->tracks->create($file, $album);
					}
				});
			} else {
				return false;
			}
			return $album;
		}	
	}

	public function createZip(Album $album)
	{
		if ($album->tracks->count() > 1) {
				// Create  new Zip File  and  Delete old  one
			$compress = Download::from($album);
			if ($compress) {
				$file = $compress instanceof SplFileInfo ? $compress : new SplFileInfo($compress);
				$file = new File($file->getRealPath());
				$filename = $album->slug . '-full-zipped-album';

				if ($album->hasMedia('zip')) {
					$zipped = $album->getMedia('zip');
				}
				$zip = $album->addMedia($file)
						->usingName("{$album->full_title} Zipped Album")
						->usingFileName(str_slug($album->slug) . "-full-zipped-album.{$file->guessExtension()}")
						->toMediaLibrary('zip');
				
				if ($zip) {
					if (isset($zipped)) $zipped->each->delete();
					return true;
				}
				return false;
			}
		}
		return false;
	}

	public function uploadCover($data)
	{
		$cover = $data['cover'];
		if (!$this->validate->image($cover)) return false;

		$album = $this->query()->findOrFail($data['albumId']);
		if (isset($cover)) {
			if ($album->hasMedia('cover')) {
				$album->getMedia('cover')->each->delete();
			}
			$cover = $album->copyMedia($cover)
					->usingName($album->full_title)
					->usingFileName(str_slug($album->full_title) . ".{$cover->extension()}")
					->toMediaLibrary('cover');

			if ($cover) {
				$optimizerChain = OptimizerChainFactory::create();
				$optimizerChain->optimize($cover->getPath());
				
				Image::load($cover->getPath())
			   ->width(310)
			   ->height(330)
			   ->save();
			}

			return $cover ? true : false;
		}
		return false;
	}

	public function createAlbumStub($input)
	{
		$album = self::MODEL;
		$album = new $album;
		$album->title = $input['title'];
		$album->description = $input['description'];
		$album->type = $input['type'];
		
		return $album;
	}

    public function delete(Album $album)
	{
		if ($album->delete()) {
			event(new AlbumDeleted($album));

			return true;
		}

		throw new GeneralException(trans('exceptions.backend.music.albums.delete_error'));
		
	}
}