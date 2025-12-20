<?php

namespace App\Repositories\Eloquent;


use App\Models\Dispute;
use App\Repositories\IDisputeRepositories;


class DisputeRepository  extends BaseRepository implements IDisputeRepositories
{
    public function __construct()
    {
        $this->model = new Dispute();
    }

}
