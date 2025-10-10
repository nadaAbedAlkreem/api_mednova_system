<?php

namespace App\Repositories\Eloquent;

use App\Models\RehabilitationCenter;
use App\Models\Therapist;
use App\Repositories\IRehabilitationCenterRepositories;
use App\Repositories\ITherapistRepositories;


class RehabilitationCenterRepository  extends BaseRepository implements IRehabilitationCenterRepositories
{
    public function __construct()
    {
        $this->model = new RehabilitationCenter();
    }

}
