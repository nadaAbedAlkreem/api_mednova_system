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

    public function getFilteredSubscribedUsers($today , array $filters = [], int $limit = 10 )
    {
        $query = $this->model->with(['customer', 'package'])
            ->where('is_active', 1)
            ->where('starts_at', '<=', $today)
            ->where('ends_at', '>=', $today);

        // فلترة حسب اسم العميل
        if (!empty($filters['search'])) {
            $query->whereHas('customer', function ($q) use ($filters) {
                $q->where('full_name', 'LIKE', '%' . $filters['customer_name'] . '%');
                $q->where('email', 'LIKE', '%' . $filters['email'] . '%');
            });
        }
        // فلترة حسب اسم الباكيج
        if (!empty($filters['package_name'])) {
            $query->whereHas('package', function ($q) use ($filters) {
                $q->where('name', 'LIKE', '%' . $filters['package_name'] . '%');
            });
        }

        $results = $query->orderBy('id', 'desc')->paginate($limit);
        $results->appends($filters); // للحفاظ على الفلاتر في روابط الباجينيشن
        return $results;
    }
}
