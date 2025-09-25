<?php

namespace App\Repositories\Eloquent;


use App\Models\OrderNotification;
use App\Repositories\IOrderNotificationRepositories;
use App\Repositories\IOrderRepositories;
use App\Traits\ResponseTrait;

class OrderNotificationRepository extends BaseRepository implements IOrderNotificationRepositories
{
    use ResponseTrait ;
    public function __construct()
    {
        $this->model = new OrderNotification();
    }


}
