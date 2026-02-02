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
    protected UploadService $uploadService;

    protected ICustomerRepositories $customerRepositories;
    protected IRehabilitationCenterRepositories $rehabilitationCenterRepositories;
    protected ITherapistRepositories $therapistRepositories;

    protected IScheduleRepositories $scheduleRepositories;
    protected ILocationRepositories $locationRepositories;
    public function __construct(UploadService $uploadService ,IScheduleRepositories $scheduleRepositories ,ILocationRepositories $locationRepositories  ,ICustomerRepositories $customerRepositories, ITherapistRepositories $therapistRepositories , IRehabilitationCenterRepositories $rehabilitationCenterRepositories)
    {
        $this->uploadService = $uploadService;
        $this->customerRepositories = $customerRepositories;
        $this->rehabilitationCenterRepositories = $rehabilitationCenterRepositories;
        $this->therapistRepositories = $therapistRepositories;
        $this->locationRepositories = $locationRepositories;
        $this->scheduleRepositories = $scheduleRepositories;
    }


    public function prepare(array $data, $authUserTimezone = null): array
    {
        // رفع الملفات
        if (!empty($data['image'])) {
            $path = $this->uploadService->upload($data['image'], 'centerProfileImages', 'public', 'centerProfile');
            $data['image'] = asset('storage/' . $path);
        }

        if (!empty($data['license_file'])) {
            $path = $this->uploadService->upload($data['license_file'], 'license_certificate_images', 'public', 'centerLicense');
            $data['license_file'] = asset('storage/' . $path);
        }

        if (!empty($data['commercial_registration_file'])) {
            $path = $this->uploadService->upload($data['commercial_registration_file'], 'center_commercial_registration_file', 'public', 'centerCertificate');
            $data['commercial_registration_file'] = asset('storage/' . $path);
        }
        if(!empty($data['is_have_evening_time']) && $data['is_have_evening_time'] == 0)
        {
            $data['start_time_evening'] = null ;
            $data['end_time_evening'] = null ;
        }
        if(!empty($data['timezone']))
        {
            foreach (['start_time_morning', 'end_time_morning', 'start_time_evening', 'end_time_evening'] as $timeField) {
                if (!empty($data[$timeField])) {
                    $data[$timeField] = TimezoneService::toUTCHour($data[$timeField], $data['timezone']);
                }
            }
        }

//
//        // تحويل التوقيت للـ UTC
//        if ($authUserTimezone) {
//            foreach (['start_time_morning', 'end_time_morning', 'start_time_evening', 'end_time_evening'] as $timeField) {
//                if (!empty($data[$timeField])) {
//                    $data[$timeField] = TimezoneService::toUTCHour($data[$timeField], $authUserTimezone);
//                }
//            }
//        }
        $data = collect($data);
        $dataCustomer = $data->only([
            'customer_id', 'full_name', 'email', 'phone', 'gender', 'birth_date', 'image', 'timezone'
        ])->toArray();

        $dataRehabilitationCenters = $data->only([
            'name_center', 'customer_id', 'video_consultation_price', 'chat_consultation_price',
            'currency', 'year_establishment', 'license_number', 'license_authority',
            'license_file', 'bio', 'has_commercial_registration',
            'commercial_registration_number', 'commercial_registration_file', 'commercial_registration_authority'
        ])->toArray();

        $dataScheduler = $data->only([
            'day_of_week', 'start_time_morning', 'end_time_morning',
            'start_time_evening', 'end_time_evening', 'is_have_evening_time'
        ])->toArray();

        return [
            'customer' => $dataCustomer,
            'center' => $dataRehabilitationCenters,
            'schedule' => $dataScheduler,
        ];
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
