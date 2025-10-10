<?php

namespace App\Repositories\Eloquent;

use App\Models\ProgramReviewRequests;
use App\Repositories\IProgramReviewRequestsRepositories;


class ProgramReviewRequestsRepository  extends BaseRepository implements IProgramReviewRequestsRepositories
{
    public function __construct()
    {
        $this->model = new ProgramReviewRequests();
    }

}
