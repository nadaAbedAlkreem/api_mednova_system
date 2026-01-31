<?php
namespace App\Services\Api\Customer;

use App\Repositories\ICustomerRepositories;
use App\Repositories\ILocationRepositories;
use App\Repositories\IRehabilitationCenterRepositories;
use App\Repositories\IScheduleRepositories;
use App\Repositories\ITherapistRepositories;
use Illuminate\Support\Facades\DB;

class RehabilitationCenterService
{
    protected ICustomerRepositories $customerRepositories;
    protected IRehabilitationCenterRepositories $rehabilitationCenterRepositories;
    protected ITherapistRepositories $therapistRepositories;

    protected  IScheduleRepositories $scheduleRepositories;
    protected ILocationRepositories $locationRepositories;
    public function __construct(IScheduleRepositories $scheduleRepositories ,ILocationRepositories $locationRepositories  ,ICustomerRepositories $customerRepositories, ITherapistRepositories $therapistRepositories , IRehabilitationCenterRepositories $rehabilitationCenterRepositories)
    {
        $this->customerRepositories = $customerRepositories;
        $this->rehabilitationCenterRepositories = $rehabilitationCenterRepositories;
        $this->therapistRepositories = $therapistRepositories;
        $this->locationRepositories = $locationRepositories;
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
           $this->rehabilitationCenterRepositories->create($data['center']->toArray());

              // إنشاء بيانات

            $this->locationRepositories->create($data['location']->toArray());

            // إنشاء بيانات  مواعيد العمل
            $this->scheduleRepositories->create($data['schedule']->toArray());


            return $customer->load([
                'rehabilitationCenter',
                'location',
                'medicalSpecialties',
                'schedules',
            ]);
        });
    }
}
