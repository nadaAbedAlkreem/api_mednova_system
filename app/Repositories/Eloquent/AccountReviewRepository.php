<?php

namespace App\Repositories\Eloquent;

use App\Models\AccountReview;
use App\Models\AppointmentRequest;
use App\Repositories\IAccountReviewRepositories;


class AccountReviewRepository  extends BaseRepository implements IAccountReviewRepositories
{
     public function __construct()
    {
        $this->model = new AccountReview();
    }

}
