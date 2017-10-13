<?php

namespace App\Services\Music;

use Illuminate\Database\Eloquent\Model;
use App\Exceptions\GeneralException;
use getID3;
use getid3_lib;
use getid3_writetags;
use File;
use Plank\Mediable\Media;
use SplFileInfo;
use Illuminate\Http\UploadedFile;

class Tags
{
	protected $file;
	protected $path;
	protected $getID3;
	protected $error;

	public function __construct($file, $getID3 = null)
	{
		$this->file = $file;
        $this->setGetID3($getID3);
        $this->path = $this->file->path();
	}

	/**
     * @return getID3file
     */
    public function getGetID3()
    {
        return $this->getID3;
    }

    /**
     * @param getID3 $getID3
     */
    public function setGetID3($getID3 = null)
    {
        $this->getID3 = $getID3 ?: new getID3();
    }

    /**
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * @return string
     */
    public function getFile()
    {
        return $this->file;
    }

    /**
     * Get all applicable ID3 tags from the file.
     *
     * @return array
     */
    public function getInfo()
    {
    	$tags = $this->getID3->analyze($this->path);
    	// dd($tags);

    	// Copy the available tags over to comment.
        // This is a helper from getID3, though it doesn't really work well.
        // We'll still prefer getting ID3v2 tags directly later.
        // Read on.
        getid3_lib::CopyTagsToComments($tags);

        if (isset($tags['error']) || !isset($tags['playtime_seconds'])) {
            $this->error = isset($tags['error']) ? $tags['error'][0] : 'No playtime found';

            return false;
            // throw new GeneralException($this->error, 1);
        }

    	$number = 0;

        // Apparently track number can be stored with different indices as the following.
        $trackIndices = [
            'comments.track',
            'comments.tracknumber',
            'comments.track_number',
        ];

        for ($i = 0; $i < count($trackIndices) && $number === 0; ++$i) {
            $number = array_get($tags, $trackIndices[$i], [0])[0];
        }

        $props = [
            'artists' => '',
            'title' => '',
            'year' => '',
            'composer' => '',
            'duration' => $tags['playtime_string'],
            'number' => (int) $number,
            'comment' => '',
            'genres' => '',
            'lyrics' => '',
            'copyright' => '',
            'cover' => '',
            'format' => $tags['fileformat']
        ];

        $title = $this->getTitle($tags);
        $artists = $this->getArtists($tags);
        $albumArtist = $this->getAlbumArtist($tags);
        $year = $this->getYear($tags);
        $comment = $this->getComment($tags);
        $composer = $this->getComposer($tags);
        $genres = $this->getGenres($tags);
        $copyright = $this->getCopyright($tags);
        $bitrate = $this->getBitrate($tags);
        $lyrics = $this->getLyrics($tags);
        $cover = $this->getCover($tags);



        // Fixes #323, where tag names can be htmlentities()'ed
        $props['title'] = $title;
        $props['artists'] = $artists;
        $props['producer'] = $composer;
        $props['cover'] = $cover;
        $props['albumartist'] = $albumArtist;
        $props['comment'] = $comment;
        $props['genres'] = $genres;
        $props['copyright'] = $copyright;
        $props['bitrate'] = $bitrate;
        $props['lyrics'] = $lyrics;

        return $props;
    }

    public function setInfo(Model $model)
    {
    	$text_encoding = 'UTF-8';
    	$getID3 = $this->getID3;
    	$getID3->setOption(['encoding' => $text_encoding]);

    	// Initialize getID3 tag-writing module
		$tagwriter = new getid3_writetags;

		$tagwriter->filename = $this->path;
		$tagwriter->tagformats = ['id3v1', 'id3v2.3'];

		/*set various options (optional)
		if true will erase existing tag data and write only passed data;
		if false will merge passed data with existing tag data (experimental)*/
		$tagwriter->overwrite_tags    = true;

		/*if true removes other tag formats (e.g. ID3v1, ID3v2, APE, Lyrics3, etc) that may be present in 
		the file and only write the specified tag format(s). If false leaves any unspecified tag formats as-is.*/
		$tagwriter->remove_other_tags = false; 
		$tagwriter->tag_encoding = $text_encoding;

		$tag = [];
		
		if (isset($model->artist_names)) {
			$artists = $model->artists_title_comma;
		} else {
			$artists = "Unknown";
		}
		if (isset($model->features)) {
			$artists = "{$artists} {$model->features_title_comma}";
		}
		// dd($artists);
		// $artists = $this->sanitizeArtists($artists);
		$artists = array_wrap($artists);

		if (isset($model->band) && !empty($model->band)) {
			$band = $model->band;
		} else {
			$band = 'lulamusic';
		}

		$album = $model->album;

		$tag['title'] = array_wrap($model->title);
		$tag['artist'] = $artists;
		$tag['band'] = array_wrap($band);
		$tag['album'] = array_wrap($album);
		$tag['year'] = array_wrap($model->year);
		$tag['genre'] = array_wrap($model->genre);
		$tag['composer'] = array_wrap($model->composers);
		$tag['comment'] = array_wrap($model->comment);
		$tag['track'] = array_wrap($model->number);
		$tag['popularimeter'] = ['email'=>'admin@lulamusic.coom', 'rating'=>128, 'data'=>0];
		$tag['unique_file_identifier'] = ['ownerid'=>'admin@lulamusic.com', 'data'=>md5(time())];

		// Update Cover Art if Available

		if ($cover = $model->cover) {
			$cover_path  = $cover->getPath();
				$tag['attached_picture'][] = [
					'picturetypeid' => 2,
					'description' => 'cover',
					'mime' => File::mimeType($cover_path),
					'data' => File::get($cover_path),
				];
		}

		$tagwriter->tag_data = $tag;

		// write tags
		if ($tagwriter->WriteTags()) {
			if (!empty($tagwriter->warnings)) {
				throw new GeneralException($tagwriter->warnings, 1);
			}
			return;	
		} else {
			throw new GeneralException('Failed to write Tags', 1);
		}
    }

	public function getTitle($tags)
	{
		if (isset($tags['tags']['id3v2']['title'][0]) && !empty($tags['tags']['id3v2']['title'][0])) {
			$title = $this->sanitize(array_get($tags, 'tags.id3v2.title.0'));
		} elseif (isset($tags['tags']['id3v1']['title'][0]) && !empty($tags['tags']['id3v1']['title'][0])) {
			$title = $this->sanitize(array_get($tags, 'tags.id3v1.title.0'));
		} elseif (isset($tags['tags']['ape']['title'][0]) && !empty($tags['tags']['ape']['title'][0])) {
			$title = $this->sanitize(array_get($tags, 'tags.ape.title.0'));
		} else {
			if (!$title = $this->sanitize($this->file->getClientOriginalName()))
				$title = null;
		}

		return $title;
	}

	public function getArtists($tags)
	{
		if (isset($tags['tags']['id3v2']['artist']) && !empty($tags['tags']['id3v2']['artist'])) {
			$results = implode(array_get($tags, 'tags.id3v2.artist'), ",");
		} elseif (isset($tags['tags']['id3v1']['artist']) && !empty($tags['tags']['id3v1']['artist'])) {
			$results = implode(array_get($tags, 'tags.id3v1.artist'), ",");
		} elseif (isset($tags['tags']['ape']['artist']) && !empty($tags['tags']['ape']['artist'])) {
			$results = implode(array_get($tags, 'tags.ape.artist'), ",");
		} else {
			$results = null;
		}

		$results = $this->sanitizeArtists($results);

		$artists = [];
		// Figure if a track has featured artists or not
		if (!is_null($results) && preg_match("/ +ft +/i", $results)) {
			// Find The Positions of the artists and featured artists
			$position = stripos($results, ' ft ');
			$artists['main'] = substr($results, 0, $position);
			$artists['features'] = substr($results, $position + 3);
		} else {
			$artists['main'] = $results;
			$artists['features'] = null;
		}

		return $artists;
	}

	public function sanitizeArtists($artists)
	{
		$artists = str_ireplace(['&', 'and', 'vs'], ',', $artists);
		$artists = str_ireplace(['ft.', 'feat.', 'feat', 'featuring', 'featuring.', ';', '\\'], 
					'ft', $artists);

		return $artists;
	}

	public function getAlbumArtist($tags)
	{
		if (isset($tags['tags']['id3v2']['band']) && !empty($tags['tags']['id3v2']['band'])) {
			$albumArtist = implode(array_get($tags, 'tags.id3v2.band'), ",");
		} elseif (isset($tags['tags']['id3v1']['band']) && !empty($tags['tags']['id3v1']['band'])) {
			$albumArtist = implode(array_get($tags, 'tags.id3v1.band'), ",");
		} elseif (isset($tags['tags']['ape']['band']) && !empty($tags['tags']['ape']['band'])) {
			$albumArtist = implode(array_get($tags, 'tags.ape.band'), ",");
		} else {
			$albumArtist = null;
		}
		return $albumArtist;
	}

	public function getLyrics($tags)
	{
		if (isset($tags['tags']['id3v2']['unsynchronised_lyric'][null][0])
			 && !empty($tags['tags']['id3v2']['unsynchronised_lyric'][null][0])) {
			$lyrics = $this->sanitize(
					array_get($tags, 'tags.id3v2.unsynchronised_lyric', [null])[0]);
		} elseif (isset($tags['comments_html']['unsynchronised_lyric'][''][0])
			 && !empty($tags['comments_html']['unsynchronised_lyric'][''][0])) {
			$lyrics = $this->sanitize(
						array_get($tags, 'comments_html.unsynchronised_lyric', [''][0]));
		} else {
			$lyrics = null;
		}

		return $lyrics;
	}

	public function getCover($tags)
	{
		if (isset($tags['id3v2']['APIC']) 
				&& !empty($tags['id3v2']['APIC'])) {
            	$cover = $tags['id3v2']['APIC'][0];
        } elseif (isset($tags['comments']['picture'][0]['data']) 
        		&& !empty($tags['comments']['picture'][0]['data'])) {
            	$cover = $tags['comments']['picture'][0];
        } else {
            $cover = null;
        }
        return $cover;
	}

	public function getYear($tags)
	{
		if (isset($tags['tags']['id3v2']['year'][0]) &&!empty($tags['tags']['id3v2']['year'][0])) {
			$year = $this->sanitize(array_get($tags, 'tags.id3v2.year.0'));
		} elseif (isset($tags['tags']['id3v1']['year'][0]) && !empty($tags['tags']['id3v1']['year'][0])) {
			$year = $this->sanitize(array_get($tags, 'tags.id3v1.year.0'));
		} elseif (isset($tags['tags']['ape']['year'][0]) && !isset($tags['tags']['ape']['year'][0])) {
			$year = $this->sanitize(array_get($tags, 'tags.ape.year.0'));
		} else {
			$year = null;
		}

		return $year;
	}

	public function getComment($tags)
	{
		if (isset($tags['tags']['id3v2']['comment'][0]) && !empty($tags['tags']['id3v2']['comment'][0])) {
			$comment = $this->sanitize(array_get($tags, 'tags.id3v2.comment.0'));
		} elseif (isset($tags['tags']['id3v1']['comment'][0]) && !empty($tags['tags']['id3v1']['comment'][0])) {
			$comment = $this->sanitize(array_get($tags, 'tags.id3v1.comment.0'));
		} elseif (isset($tags['tags']['ape']['comment'][0]) && !empty($tags['tags']['ape']['comment'][0])) {
			$comment = $this->sanitize(array_get($tags, 'tags.ape.comment.0'));
		} else {
			$comment = null;
		}

		return $comment;
	}

	public function getComposer($tags)
	{
		if (isset($tags['tags']['id3v2']['composer'][0]) && !empty($tags['tags']['id3v2']['composer'][0])) {
			$composer = $this->sanitize(array_get($tags, 'tags.id3v2.composer.0'));
		} elseif (isset($tags['tags']['id3v1']['composer'][0]) && !empty($tags['tags']['id3v1']['composer'][0])) {
			$composer = $this->sanitize(array_get($tags, 'tags.id3v1.composer.0'));
		} elseif (isset($tags['tags']['ape']['composer'][0]) && !empty($tags['tags']['ape']['composer'][0])) {
			$composer = $this->sanitize(array_get($tags, 'tags.ape.composer.0'));
		} else {
			$composer = null;
		}

		return $composer;
	}

	public function getAlbum($tags)
	{
		if (isset($tags['tags']['id3v2']['album'][0]) && !empty($tags['tags']['id3v2']['album'][0])) {
			$album = $this->sanitize(array_get($tags, 'tags.id3v2.album.0'));
		} elseif (isset($tags['tags']['id3v1']['album'][0]) && !empty($tags['tags']['id3v1']['album'][0])) {
			$album = $this->sanitize(array_get($tags, 'tags.id3v1.album.0'));
		} elseif (isset($tags['tags']['ape']['album'][0]) && !empty($tags['tags']['ape']['album'][0])) {
			$album = $this->sanitize(array_get($tags, 'tags.ape.album.0'));
		} else {
			$album = null;
		}

		return $album;
	}

	public function getGenres($tags)
	{
		if (isset($tags['tags']['id3v2']['genre']) && !empty($tags['tags']['id3v2']['genre'])) {
			$genres = implode(array_get($tags, 'tags.id3v2.genre'), ",");
		} elseif (isset($tags['tags']['id3v1']['genre']) && !empty($tags['tags']['id3v1']['genre'])) {
			$genres = implode(array_get($tags, 'tags.id3v1.genre'), ",");
		} elseif (isset($tags['tags']['ape']['genre']) && !empty($tags['tags']['ape']['genre'])) {
			$genres = implode(array_get($tags, 'tags.ape.genre'), ",");
		} else {
			$genres = null;
		}

		return $genres;
	}

	public function getCopyright($tags)
	{
		if (isset($tags['tags']['id3v2']['copyright'][0]) && !empty($tags['tags']['id3v2']['copyright'][0])) {
			$copyright = $this->sanitize(
								array_get($tags, 'tags.id3v2.copyright_message.0'));
		} elseif (isset($tags['tags']['ape']['copyright'][0]) && !empty($tags['tags']['ape']['copyright'][0])) {
			$copyright = $this->sanitize(array_get($tags, 'tags.ape.copyright.0'));
		} else {
			$copyright = null;
		}

		return $copyright;
	}

	public function getBitrate($tags)
	{
		if (isset($tags['bitrate']) && !empty($tags['bitrate'])) {
			$bitrate = $this->sanitize(array_get($tags, 'bitrate'));
		} elseif (isset($tags['audio']['bitrate']) && !empty($tags['audio']['bitrate'])) {
			$bitrate = $this->sanitize(array_get($tags, 'audio.bitrate'));
		} else {
			$bitrate = null;
		}

		return $bitrate;
	}

	public function sanitize($value)
	{
		$value = html_entity_decode(trim($value));

		return $value;
	}

}