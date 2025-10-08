<?php

namespace App\Repositories\Eloquent;

use App\Models\Admin;
use App\Models\Patient;
use App\Repositories\IAdminRepositories;
use App\Repositories\IPatientRepositories;


class PatientRepository  extends BaseRepository implements IPatientRepositories
{
    public function __construct()
    {
        $this->model = new Patient();
    }

}
