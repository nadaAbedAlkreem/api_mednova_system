<?php

namespace App\Repositories\Eloquent;

use App\Models\Admin;
use App\Models\ServiceProvider;
use App\Repositories\IAdminRepositories;
use App\Repositories\IServiceProviderRepositories;


class ServiceProviderRepository  extends BaseRepository implements IServiceProviderRepositories
{
    public function __construct()
    {
        $this->model = new ServiceProvider();
    }

}
