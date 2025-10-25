<?php

namespace App\Repositories\Eloquent;


use App\Models\Rating;
use App\Repositories\IRatingRepositories;


class RatingRepository  extends BaseRepository implements   IRatingRepositories
{
    public function __construct()
    {
        $this->model = new Rating();
    }

}
