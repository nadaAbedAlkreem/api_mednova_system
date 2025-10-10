<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Http\Resources\CustomerResource;
use App\Http\Resources\MedicalSpecialtyResource;
use App\Models\MedicalSpecialtie;
use App\Repositories\ICustomerRepositories;
use App\Traits\ResponseTrait;
use Illuminate\Http\Request;

class MedicalSpecialtieController extends Controller
{
    use ResponseTrait;
    protected ICustomerRepositories $customerRepositories;
    public function __construct(ICustomerRepositories $customerRepositories)
    {
        $this->customerRepositories = $customerRepositories;
    }
    /**
     * Display a listing of the resource.
     */
    public function getAll(): \Illuminate\Http\JsonResponse
    {
        try {
           $medicalSpecialties = MedicalSpecialtie::all();
           return $this->successResponse(__('messages.DATA_RETRIEVED_SUCCESSFULLY'),  MedicalSpecialtyResource::collection($medicalSpecialties), 201);

        }catch (\Exception $exception){
            return $this->errorResponse(__('messages.ERROR_OCCURRED'), ['error' => $exception->getMessage()], 500);

        }
    }

    public function getServiceProviderDependMedicalSpecialties(Request $request): \Illuminate\Http\JsonResponse
    {
        try {
            $providerType = $request->query('provider_type');
            $limit = $request->query('limit');
            $relation = [];
            $condition = [];
            if($providerType == 'therapist')
            {
                $relation = ['therapist' , 'therapist.specialty'];
                $condition = ['type_account' => 'therapist'];
            }
            if($providerType == 'center')
            {
                $relation = ['rehabilitationCenter' , 'medicalSpecialties'];
                $condition = ['type_account' => 'rehabilitation_center'];
            }
            if($providerType == 'all')
            {
                $relation = ['rehabilitationCenter' , 'medicalSpecialties','therapist' , 'therapist.specialty'];
                $condition = ['type_account' => ['rehabilitation_center', 'therapist']];
            }
            $data = $this->customerRepositories->paginateInWhereWith($condition, $relation , ['column' => 'id', 'dir' => 'DESC'] ,$limit) ;
            return $this->successResponse(__('messages.DATA_RETRIEVED_SUCCESSFULLY'),  CustomerResource::collection($data), 200,);
        }catch (\Exception $e){
            return $this->errorResponse(__('messages.ERROR_OCCURRED'), ['error' => $e->getMessage()], 500);

        }
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreMedicalSpecialtieRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(MedicalSpecialtie $medicalSpecialtie)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(MedicalSpecialtie $medicalSpecialtie)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateMedicalSpecialtieRequest $request, MedicalSpecialtie $medicalSpecialtie)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(MedicalSpecialtie $medicalSpecialtie)
    {
        //
    }
}
