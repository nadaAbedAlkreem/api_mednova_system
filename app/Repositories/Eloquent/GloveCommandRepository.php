<?php

namespace App\Repositories\Eloquent;

use App\Models\GloveCommand;
use App\Models\GloveData;
use App\Repositories\IGloveCommandRepositories;


class GloveCommandRepository  extends BaseRepository implements IGloveCommandRepositories
{
    public function __construct()
    {
        $this->model = new GloveCommand();
    }

}
