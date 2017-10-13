<?php 

namespace App\Repositories\Backend\Music;

use App\Models\Music\Artist\Artist;
use Illuminate\Support\Facades\DB;
use App\Repositories\BaseRepository;
use App\Models\Music\Track\Track;
use App\Models\Music\Album\Album;
use App\Exceptions\GeneralException;
use App\Helpers\Validators\Mimes;
use Illuminate\Http\UploadedFile;
use SplFileInfo;
use App\Services\Music\Tags;
use Storage;
use Spatie\Image\Image;
use Spatie\ImageOptimizer\OptimizerChainFactory;

class TrackRepository extends BaseRepository
{
	const MODEL = Track::class;

	protected $artists;
	protected $validate;

	public function __construct(ArtistRepository $artists, Mimes $validate)
	{
		$this->artists = $artists;
		$this->validate = $validate;
	}

	public function create($file, $model)
	{
		if (!$tags = (new Tags($file))->getInfo()) return false;

		// Make A Track Model and Save
		$track = $this->createTrackStub($tags);
		// If Model has Cover, Attach its Path to Track
		// $track->cover_path = $model->cover_path;
		// Save Track
		$track->save();
		// Create Artist Entity or Fetch One
		$tags['main'] = $tags['artists']['main'];
		$tags['features'] = $tags['artists']['features'];

		$this->attachArtists($track, $tags);
		
		// Set Track Slug 
		$this->setSlug($track);

		if (!$this->validate->audio($file)) return false;

		$track->addMedia($file)
				->usingName($track->full_title)
				->usingFileName(str_slug($track->full_title) . ".{$tags['format']}")
				->toMediaLibrary('file');

		if ($model->hasMedia('cover'))  {
			$modelCover = $model->getFirstMedia('cover');
			$cover = $track->copyMedia($modelCover->getPath())
					->usingName("{$track->fresh()->full_title} Cover")
					->usingFileName("{$track->slug}-cover.{$modelCover->getExtensionAttribute()}")
					->toMediaLibrary('cover');
		} elseif (!$model->hasMedia('cover') && isset($tags['cover']) && !empty($tags['cover']['data'])) {
			// If Model has no Cover, extract it from file and Attach to Track
			$extension = explode('/', $tags['cover']['image_mime']);
	        $extension = empty($extension[1]) ? 'png' : $extension[1];
			$cover = $track->addMediaFromBase64(
					base64_encode($tags['cover']['data']), $tags['cover']['image_mime'])
					->usingName("{$track->fresh()->full_title} Cover")
					->usingFileName("{$track->slug}-cover.{$extension}")
					->toMediaLibrary('cover');
		}

		if ($cover) {
			$optimizerChain = OptimizerChainFactory::create();
			$optimizerChain->optimize($cover->getPath());
			
			Image::load($cover->getPath())
		   ->width(310)
		   ->height(330)
		   ->save();
		}

		if ($model->attachTrack($track)) {
			$this->setFileTags($track->fresh());
			return $track;
		} else {
			$track->forceDelete();
			return false;
		}
	}

	public function update(Track $track, array $input)
	{
		// Detach All Artists From Track
		$track->artists()->detach();
		// Update Track
		$track = $this->updateTrackStub($track, $input);

		// Update Track Cover
		if (!empty($cover = $input['cover']) && $cover->isValid()) {
			// $this->updateTrackCover($track, $input['cover']);
			if ($this->validate->image($cover)) {
				if ($track->hasMedia('cover')) {
					$track->getMedia('cover')->each->delete();
				}

				$track->addMedia($cover)
					->usingName("{$track->full_title}-cover")
					->usingFileName(str_slug($track->full_title) . "-cover.{$cover->extension()}")
					->toMediaLibrary('cover');
				}
			}

		$this->attachArtists($track, $input);

		// Set Track Slug 
		$this->setSlug($track);

		$track->save();

		if ($track->trackable->attachTrack($track->fresh())) {
			// Update File Tags
			$this->setFileTags($track->fresh());
		} 

		return $track;
	}

	public function setSlug(Track $track)
	{
		$slug = str_slug("{$track->fresh()->full_title}");
		if ($slug != $track->slug && $this->query()->similarSlug($slug)->exists()) {
            $count = 1;
            while ($this->query()->similarSlug(
            		$slug = str_slug("{$track->full_title}-{$count}"))->exists()) {
            	$count++;
            }
        }
        $track->update(['slug' => $slug]);
	}

	public function attachArtists(Track $track, array $data)
	{
		$artists = $data['main'];
		$features = $data['features'];
		// Create Artist Entity or Fetch One
		if (isset($artists) && !is_null($artists)) {
			$artists = explode(',', $artists);
			foreach ($artists as $artist) {
				$artist = $this->artists->createArtistStub($artist);
				$track->artists()->attach($artist);
			}
		} else {
			$track->artists()->attach(Artist::UNKNOWN_ID);
		}

		// If Featured Artist isset, Create it or Fetch One
		if (isset($features) && !is_null($features)) {
			$features = explode(',', $features);
			foreach ($features as $feature) {
				$feature = $this->artists->createArtistStub($feature);
				if (($track->fresh()->artists)->contains($feature)) continue;
				$track->artists()->attach($feature, ['role' => 'feature']);
			}
		}
		// dd($data['composer']);
		// If Featured Artist isset, Create it or Fetch One
		if (isset($data['producer']) && !is_null($data['producer'])) {
			$producer = $this->artists->createArtistStub($data['producer']);
			$track->artists()->attach($producer, ['role' => 'producer']);
		} else {
			$producer = $track->fresh()->artists->filter(function ($artist) {
				return $artist->pivot->role == 'main';
			})->first();
			$track->artists()->attach($producer, ['role' => 'producer']);
		}
	}

	public function updateTrackCover(Track $track, $data)
	{
		$directory = $this->generateCoverPath($track->file);
		$cover = $this->upload->image($data, 'uploads', $directory);
		$track->cover_path = $cover->getUrl();
	}

	public function updateTrackStub(Track $track, $input)
	{
		$title = title_case($input['title']);		

		$track->title = $title;
		$track->year = $input['year'];
		$track->number = $input['number'];
		if (isset($input['comment'])) {
			$track->comment = $input['comment'];
		} else {
			$track->comment = 'Downloaded Free From www.lulamusic.com';
		}
		if (isset($input['copyright'])) {
			$track->copyright = $input['copyright'];
		}

		$track->save();

		return $track;
	}

	public function createTrackStub($tags)
	{
		$title = $tags['title'];

		if (!is_null($title) && isset($tags['duration'])) {

			$track = self::MODEL;
			$track = new $track;

			$track->title = $title;
			$track->year = $tags['year'];
			$track->number = $tags['number'];
			if (isset($tags['comment'])) {
				$track->comment = $tags['comment'];
			} else {
				$track->comment = 'Downloaded Free From www.lulamusic.com';
			}
			$track->bitrate = $tags['bitrate'];
			$track->duration = $tags['duration'];
			if (isset($tags['copyright'])) {
				$track->copyright = $tags['copyright'];
			}
			
			return $track;
		} else {
			return false;
		}
	}

	/**
     * Generate a random path for the cover image.
     *
     * @param string $extension The extension of the cover (without dot)
     *
     * @return string
     */
    private function generateCoverPath($file)
    {
        return $file->directory . '/covers';
    }

	/**
     * Generate a cover from provided data.
     */
    public function generateCover(array $cover, Track $track)
    {
        $extension = explode('/', $cover['image_mime']);
        $extension = empty($extension[1]) ? 'png' : $extension[1];

        $cover = $this->writeCoverFile($cover['data'], $extension, $track);

        return $cover;
    }

    /**
     * Write a cover image file with binary data and update the Album with the new cover file.
     *
     * @param string $binaryData
     * @param string $extension  The file extension
     */
    public function writeCoverFile($binaryData, $extension, Track $track)
    {
    	$file = $track->file;
        $extension = trim(strtolower($extension), '. ');
        $directory = $this->generateCoverPath($file);

        $store = Storage::disk($file->disk)
        	->put($directory . '/' .  $file->filename . '.' . $extension, $binaryData);

        if ($store) {
        	$cover = $this->upload
        		->import($file->disk, $directory, $file->filename, $extension);

        	return $cover;
        }
        return false;    
    }

    public function attachCover(Track $track, array $binary)
    {
		$cover = $this->generateCover($binary, $track);
		if ($cover) {
			$track->cover_path = $cover->getUrl();
		}
    }

    public function setFileTags(Track $track)
    {
    	$path = $track->path;
		$file = $path instanceof SplFileInfo ? $path : new SplFileInfo($path);
		$file = new UploadedFile($file->getRealPath(), true);
		$track->band = $track->trackable->artists_title_comma;
		$track->album = $track->trackable->title ?: "NambaNamba Downloads";
		$track->genre = $track->trackable->genre->name;
		(new Tags($file))->setInfo($track);
    }

    public function delete(Track $track)
    {
    	$track->delete();
    }
}