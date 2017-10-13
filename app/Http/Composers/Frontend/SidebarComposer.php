<?php

namespace App\Http\Composers\Frontend;

use Illuminate\View\View;
use App\Repositories\Backend\Music\CategoryRepository;
use Illuminate\Support\Facades\Cache;
use App\Models\Music\Album\Album;
use App\Models\Music\Single\Single;

/**
 * Class SidebarComposer.
 */
class SidebarComposer
{
    /**
     * @var UserRepository
     */
    protected $categories;

    /**
     * SidebarComposer constructor.
     *
     * @param UserRepository $userRepository
     */
    public function __construct(CategoryRepository $categories)
    {
        $this->categories = $categories;
    }

    /**
     * @param View $view
     *
     * @return bool|mixed
     */
    public function compose(View $view)
    {
        $categories = Cache::rememberForever("categories", function () {
            return $this->categories->query()->with('genres')->get();
        });
        $view->with('categories', $categories);
    }
}
