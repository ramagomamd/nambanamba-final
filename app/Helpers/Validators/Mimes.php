<?php

namespace App\Helpers\Validators;

/**
 * Class Mimes.
 */
class Mimes
{
    public function audio($file)
    {
        $mime_types = collect([
                'audio/aac',
                'audio/ogg',
                'audio/mpeg',
                'audio/mp3',
                'audio/mpeg',
                'audio/wav'
            ]);

        $mime_type = $file->getMimeType();

        if ($mime_types->contains($mime_type)) {
            return true;
        }
        return false;
    }

    public function image($file)
    {
        $extensions = collect([
                'jpg',
                'jpeg',
                'png',
                'gif',
        ]);

        $extension = $file->extension();

        if ($extensions->contains($extension)) {
            return true;
        }
        return false;
    }
}
