<?php

namespace App\Repositories\Eloquent;

use App\Models\AppointmentRequest;
use App\Repositories\IAppointmentRequestRepositories;


class AppointmentRequestRepository  extends BaseRepository implements IAppointmentRequestRepositories
{
     public function __construct()
    {
        $this->model = new AppointmentRequest();
    }

}
