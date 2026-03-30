<?php

namespace App\Repositories\Eloquent;

use App\Models\Admin;
use App\Models\Card;
use App\Models\Report;
use App\Models\UserPackage;
use App\Repositories\IAdminRepositories;
use App\Repositories\IAuthRepositories;
use App\Repositories\ICardRepositories;
use App\Repositories\IReportRepositories;
use App\Repositories\IUserPackageRepositories;
use App\Traits\ResponseTrait;
use Illuminate\Support\Facades\Auth;

class UserPackageRepository extends BaseRepository implements IUserPackageRepositories
{
    public function __construct()
    {
        $this->model = new UserPackage();
    }
}
