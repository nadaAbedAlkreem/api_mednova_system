<?php

namespace App\Repositories\Eloquent;

use App\Models\DeviceRequest;
use App\Models\GloveData;
use App\Repositories\IDeviceRequestRepositories;
use App\Repositories\IGloveDataRepositories;


class GloveDataRepository  extends BaseRepository implements IGloveDataRepositories
{
    public function __construct()
    {
        $this->model = new GloveData();
    }

}
