<?php

namespace App\Repositories\Eloquent;

use App\Models\Location;
use App\Repositories\ILocationRepositories;


class LocationRepository  extends BaseRepository implements ILocationRepositories
{
     public function __construct()
    {
        $this->model = new Location();
    }

}
