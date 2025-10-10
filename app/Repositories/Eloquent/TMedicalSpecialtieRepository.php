<?php

namespace App\Repositories\Eloquent;

use App\Models\MedicalSpecialtie;
use App\Repositories\IMedicalSpecialtieRepositories;


class TMedicalSpecialtieRepository  extends BaseRepository implements IMedicalSpecialtieRepositories
{
    public function __construct()
    {
        $this->model = new MedicalSpecialtie();
    }

}
