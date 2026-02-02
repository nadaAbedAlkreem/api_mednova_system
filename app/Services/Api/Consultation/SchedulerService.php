<?php

namespace App\Services\Api\Consultation;




use App\Enums\ConsultantType;
use App\Repositories\IRehabilitationCenterRepositories;

class SchedulerService
{

    protected IRehabilitationCenterRepositories $rehabilitationCenterRepositories;

    public function __construct(IRehabilitationCenterRepositories $rehabilitationCenterRepositories)
    {
      $this->rehabilitationCenterRepositories = $rehabilitationCenterRepositories;
    }
    public function update(int $serviceProviderId, ConsultantType $typeAccount, array $data): void
    {
        match ($typeAccount) {
            ConsultantType::REHABILITATION_CENTER =>
            $this->rehabilitationCenterRepositories->updateWhere(
                $data,
                ['consultant_id' => $serviceProviderId, 'type_account' => $typeAccount]
            ),
        };


    }


}
