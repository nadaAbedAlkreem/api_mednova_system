<?php

namespace App\Repositories\Eloquent;

use App\Models\ConsultationVideoRequest;
use App\Repositories\IConsultationVideoRequestRepositories;


class ConsultationVideoRequestRepository  extends BaseRepository implements IConsultationVideoRequestRepositories
{
     public function __construct()
    {
        $this->model = new ConsultationVideoRequest();
    }

}
