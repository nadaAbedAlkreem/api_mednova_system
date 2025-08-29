<?php

namespace App\Repositories\Eloquent;

use App\Models\Admin;
use App\Models\Order;
use App\Repositories\IAdminRepositories;
use App\Repositories\IAuthRepositories;
use App\Repositories\IOrderRepositories;
use App\Traits\ResponseTrait;
use Illuminate\Support\Facades\Auth;

class OrderRepository extends BaseRepository implements IOrderRepositories
{
    use ResponseTrait ;
    public function __construct()
    {
        $this->model = new Order();
    }


}
