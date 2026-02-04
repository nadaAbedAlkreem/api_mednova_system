<?php

namespace App\Services\Api\Consultation;




use App\Enums\ConsultantType;
use App\Repositories\IScheduleRepositories;

class SchedulerService
{

    protected IScheduleRepositories $scheduleRepositories;

    public function __construct(IScheduleRepositories $scheduleRepositories )
    {
     $this->scheduleRepositories = $scheduleRepositories;
    }
    public function update(int $serviceProviderId, ConsultantType $typeAccount, array $data): void
    {
     $this->scheduleRepositories->updateWhere($data, ['consultant_id' => $serviceProviderId, 'consultant_type' => $typeAccount]);

    }


}
