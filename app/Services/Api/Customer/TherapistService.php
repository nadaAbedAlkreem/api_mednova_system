<?php
namespace App\Services\Api\Customer;

use App\Enums\ConsultantType;
use App\Repositories\ICustomerRepositories;
use App\Repositories\ILocationRepositories;
use App\Repositories\IRehabilitationCenterRepositories;
use App\Repositories\IScheduleRepositories;
use App\Repositories\ITherapistRepositories;
use Illuminate\Support\Facades\DB;

class TherapistService
{
    protected UploadService $uploadService;
    protected ITherapistRepositories $therapistRepositories;
    protected IScheduleRepositories $scheduleRepositories;
    protected ICustomerRepositories $customerRepositories;
    protected ILocationRepositories $locationRepositories;
    public function __construct(ILocationRepositories $locationRepositories, ICustomerRepositories $customerRepositories , UploadService $uploadService ,IScheduleRepositories $scheduleRepositories  , ITherapistRepositories $therapistRepositories )
    {
        $this->uploadService = $uploadService;
        $this->therapistRepositories = $therapistRepositories;
        $this->scheduleRepositories = $scheduleRepositories;
        $this->customerRepositories = $customerRepositories;
        $this->locationRepositories = $locationRepositories;
    }


    public function prepare(array $data, $authUserTimezone = null): array
    {
        // رفع الملفات
        if (!empty($data['image'])) {
            $path = $this->uploadService->upload($data['image'], 'therapist_profile_images' ,'public' ,'therapist_profile');
            $data['image'] =  asset('storage/' . $path);
        }

        if (!empty($data['certificate_file'])) {
            $path = $this->uploadService->upload($data['certificate_file'], 'therapist_certificate_images' ,'public' ,'therapistCertificate');
            $data['certificate_file'] =  asset('storage/' . $path);
        }

        if (!empty($data['license_file'])) {
            $path = $this->uploadService->upload($data['license_file'], 'license_certificate_images','public', 'therapistLicense');
            $data['license_file'] =  asset('storage/' . $path);
        }
        $data['consultant_id'] =  $data['customer_id'] ;
        $data['consultant_type'] = 'therapist' ;
        $data['type'] = 'online' ;
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

        $data['consultant_id'] = $data['customer_id'];
        $data['consultant_type'] = ConsultantType::THERAPIST;
        if(isset($data['day_of_week']))
        {
            $data['day_of_week'] = json_encode($data['day_of_week']);
        }
        $data['type'] = 'online';
        $data = collect($data);
        $dataCustomer = $data->only([
            'customer_id', 'full_name', 'email', 'phone', 'gender', 'birth_date', 'image', 'timezone'
        ])->toArray();

        $dataTherapist = $data->only([
            'customer_id',
            'medical_specialties_id',
            'experience_years' ,
            'university_name' ,
            'countries_certified' ,
            'graduation_year' ,
            'certificate_file' ,
            'license_number' ,
            'license_authority' ,
            'video_consultation_price' ,
            'chat_consultation_price' ,
            'currency' ,
            'bio' ,
            'license_file'
        ])->toArray();

        $dataScheduler = $data->only(['type', 'consultant_id', 'consultant_type', 'day_of_week', 'start_time_morning', 'end_time_morning',
            'start_time_evening', 'end_time_evening', 'is_have_evening_time'
        ])->toArray();
        $dataLocation = $data->only([
            'customer_id',
            'formatted_address', 'country', 'city'
        ])->toArray();

        return [
            'customer' => $dataCustomer,
            'therapist' => $dataTherapist,
            'schedule' => $dataScheduler,
            'location' => $dataLocation
        ];
     }






    public function store(array $data, int $customerId)
    {
        return DB::transaction(function () use ($data, $customerId) {

            // تحديث بيانات العميل
            $customer = $this->customerRepositories->updateAndReturn($data['customer'], $customerId);

//            // مزامنة التخصصات الطبية إذا وجدت
//            if (!empty($specialtyIds)) {
//                $customer->medicalSpecialties()->sync($specialtyIds);
//            }
            $this->locationRepositories->create($data['location']);

            // إنشاء بيانات مركز التأهيل
            $this->therapistRepositories->create($data['therapist']);
              // إنشاء بيانات


            // إنشاء بيانات  مواعيد العمل
            $this->scheduleRepositories->create($data['schedule']);


            return $customer->load([
                'rehabilitationCenter',
                'location',
                'medicalSpecialties',
                'schedules',
            ]);
        });
    }





}
