<?php

use Illuminate\Database\Seeder;
use App\Models\Music\Category\Category;
use App\Models\Music\Genre\Genre;
use Illuminate\Support\Facades\Cache;

/**
 * Class MusicTableSeeder.
 */
class MusicTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Cache::flush();
        $genres = [

            [
                'name'              => 'House',
                'slug'              => str_slug('House'),
            ],


            [
                'name'              => 'Hip Hop',
                'slug'              => str_slug('Hip Hop'),
            ],

            [
                'name'              => 'Gospel',
                'slug'              => str_slug('Gospel'),
            ],

            [
                'name'              => 'Kwaito',
                'slug'              => str_slug('Kwaito'),
            ],
        ];

        foreach ($genres as $genre) {
            Genre::forceCreate($genre);
        }

        $categories = [
            [
                'name'              => 'Music',
                'slug'              => str_slug('Music'),
            ],

            [
                'name'              => 'Mzansi',
                'slug'              => str_slug('Mzansi'),
            ],

            [
                'name'              => 'African',
                'slug'              => str_slug('African'),
            ],

            [
                'name'              => 'International',
                'slug'              => str_slug('International'),
            ],
        ];
        foreach ($categories as $category) {
            Category::forceCreate($category);
        }
    }
}
