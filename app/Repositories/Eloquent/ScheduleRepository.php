<?php

namespace App\Repositories\Eloquent;

use App\Models\Schedule;
use App\Repositories\IScheduleRepositories;


class ScheduleRepository  extends BaseRepository implements IScheduleRepositories
{
    public function __construct()
    {
        $this->model = new Schedule();
    }

}
