<?php

namespace App\Repositories\Eloquent;

use App\Models\Admin;
use App\Models\Report;
use App\Repositories\IAdminRepositories;
use App\Repositories\IAuthRepositories;
use App\Repositories\IReportRepositories;
use App\Traits\ResponseTrait;
use Illuminate\Support\Facades\Auth;

class ReportRepository extends BaseRepository implements IReportRepositories
{
    public function __construct()
    {
        $this->model = new Report();
    }
}
