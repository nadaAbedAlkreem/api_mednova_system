<?php
namespace App\Services\api;

use App\Models\User;
use App\Repositories\ICustomerRepositories;
use App\Repositories\IRehabilitationCenterRepositories;
use App\Repositories\IScheduleRepositories;
use App\Repositories\ITherapistRepositories;
use Exception;
use Illuminate\Support\Facades\DB;

class RehabilitationCenterService
{
    protected ICustomerRepositories $customerRepositories;
    protected IRehabilitationCenterRepositories $rehabilitationCenterRepositories;
    protected ITherapistRepositories $therapistRepositories;
    protected IScheduleRepositories $scheduleRepositories;


    public function __construct(IScheduleRepositories $scheduleRepositories ,ICustomerRepositories $customerRepositories, ITherapistRepositories $therapistRepositories , IRehabilitationCenterRepositories $rehabilitationCenterRepositories)
    {
        $this->customerRepositories = $customerRepositories;
        $this->rehabilitationCenterRepositories = $rehabilitationCenterRepositories;
        $this->therapistRepositories = $therapistRepositories;
        $this->scheduleRepositories = $scheduleRepositories;
    }

    public function store(array $data, int $customerId, array $specialtyIds)
    {
        return DB::transaction(function () use ($data, $customerId, $specialtyIds) {

            // تحديث بيانات العميل
            $customer = $this->customerRepositories->updateAndReturn($data['customer']->toArray(), $customerId);

            // مزامنة التخصصات الطبية إذا وجدت
            if (!empty($specialtyIds)) {
                $customer->medicalSpecialties()->sync($specialtyIds);
            }

            // إنشاء بيانات مركز التأهيل
            $center = $this->rehabilitationCenterRepositories->create($data['center']->toArray());

            // إنشاء الجداول الزمنية لكل يوم
            $schedules = [];
            $scheduleData = $data['schedule']->toArray();
           $this->scheduleRepositories->create($scheduleData);

            return $customer->load([
                'rehabilitationCenter',
                'location',
                'medicalSpecialties',
                'schedules',
            ]);
        });
    }
}
