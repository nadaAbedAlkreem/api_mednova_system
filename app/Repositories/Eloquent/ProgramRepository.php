<?php

namespace App\Repositories\Eloquent;

use App\Models\Program;
use App\Repositories\IProgramRepositories;


class ProgramRepository  extends BaseRepository implements IProgramRepositories
{
    public function __construct()
    {
        $this->model = new Program();
    }

}
