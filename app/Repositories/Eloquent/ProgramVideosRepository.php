<?php

namespace App\Repositories\Eloquent;

use App\Models\Program;
use App\Models\ProgramVideos;
use App\Repositories\IProgramRepositories;
use App\Repositories\IProgramVideosRepositories;


class ProgramVideosRepository  extends BaseRepository implements IProgramVideosRepositories
{
    public function __construct()
    {
        $this->model = new programVideos();
    }

}
