<?php

namespace App\Repositories\Eloquent;

use App\Models\DeviceRequest;
use App\Models\GloveData;
use App\Models\GloveDevice;
use App\Repositories\IDeviceRequestRepositories;
use App\Repositories\IGloveDataRepositories;
use App\Repositories\IGloveDeviceRepositories;


class GloveDeviceRepository  extends BaseRepository implements IGloveDeviceRepositories
{
    public function __construct()
    {
        $this->model = new GloveDevice();
    }

}
