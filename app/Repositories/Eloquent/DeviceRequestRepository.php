<?php

namespace App\Repositories\Eloquent;

use App\Models\DeviceRequest;
use App\Repositories\IDeviceRequestRepositories;


class DeviceRequestRepository  extends BaseRepository implements IDeviceRequestRepositories
{
    public function __construct()
    {
        $this->model = new DeviceRequest();
    }

}
