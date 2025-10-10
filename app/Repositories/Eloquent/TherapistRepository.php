<?php

namespace App\Repositories\Eloquent;

use App\Models\Therapist;
use App\Repositories\ITherapistRepositories;


class TherapistRepository  extends BaseRepository implements ITherapistRepositories
{
    public function __construct()
    {
        $this->model = new Therapist();
    }

}
