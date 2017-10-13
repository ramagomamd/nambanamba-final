<?php 

namespace App\Repositories\Backend\Music;

use App\Repositories\BaseRepository;
use App\Models\Music\Single\Single;
use App\Events\Backend\Music\Single\SingleCreated;
use App\Events\Backend\Music\Single\SingleUpdated;
use App\Events\Backend\Music\Single\SingleDeleted;
use App\Exceptions\GeneralException;
use App\Services\Music\Tags;

class SingleRepository extends BaseRepository
{
	const MODEL = Single::class;

	protected $categories;
	protected $genres;
	protected $tracks;

	public function __construct(GenreRepository $genres, TrackRepository $tracks,
								CategoryRepository $categories)
	{
		$this->categories = $categories;
		$this->genres = $genres;
		$this->tracks = $tracks;
	}

	public function create(array $input)
	{	
		$file = $input['file'];
		// Fetch files ID3 Tags
		if (!$tags = (new Tags($file))->getInfo()) {
			return [
				'message' => 'Failed to read file ID3 Tags',
				'code' => 508
			];
		}

		$single = self::MODEL;
		$single = new $single;
		
		// Attach Category
		if (isset($input['category']) && !is_null($input['category'])) {
			// Sync Main Category
			$category = $this->categories->createCategoryStub($input['category']);
			$single->category()->associate($category);
		} else {
			return [
				'message' => 'Failed to create category for the single',
				'code' => 508
			];
		}

		// Create and Attach Genres
		if (isset($input['genre']) && !is_null($input['genre'])) {
			$genre = $this->genres->createGenreStub($input['genre']);
			$single->genre()->associate($genre);
		} else {
			return [
				'message' => 'Failed to create genre for the single',
				'code' => 508
			];
		}

		$single->save();

		if ($single && $file->isValid()) {
			$track = $this->tracks->create($file, $single);

			if (!$track) {
				$single->forceDelete();
				return [
					'message' => 'Failed to save track to database',
					'code' => 508
				];
			}
		}
		
		return [
			'message' => 'Successfully Uploaded File to server',
			'code' => 201
		];
	}

	public function update(Single $single, array $input)
	{
		$single->description = $input['description'];

		if (!empty($input['category'])) {
			// Detach Category
			$single->category()->dissociate();
			// Create and Attach Category
			$category = $this->categories->createCategoryStub($input['category']);
			$single->category()->associate($category);
		} else {
			return [
				'message' => 'Failed to create category for the single',
				'code' => 508
			];
		}

		if (isset($input['genre']) && !is_null($input['genre'])) {
			// Detach Genre
			$single->genre()->dissociate();
			// Create and Attach Genre
			$genre = $this->genres->createGenreStub($input['genre']);
			$single->genre()->associate($genre);
		} else {
			return [
				'message' => 'Failed to create genre for the single',
				'code' => 508
			];
		}

		$single->save();

		$file = $input['file'];
		if (isset($file) && $file->isValid()) {
			$track = $this->tracks->create($file, $single);
		}
		return $single;
	}

	public function delete(Single $single)
	{
		if ($single->track()->delete() && $single->delete()) {
			event(new SingleDeleted($single));

			$data = [
				'flash_success' => trans('alerts.backend.music.singles.deleted')
			];
		} else {
			$data = [
				'flash_success' => trans('exceptions.backend.music.singles.delete_error')
			];
		}
		return $data;
		
	}
}