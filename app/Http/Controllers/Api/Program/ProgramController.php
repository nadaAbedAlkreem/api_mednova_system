<?php

namespace App\Http\Controllers\Api\Program;

use App\Http\Controllers\Controller;
use App\Http\Requests\api\program\StoreProgramRequest;
use App\Http\Requests\api\program\UpdateProgramRequest;
use App\Http\Resources\Api\Program\ProgramResource;
use App\Models\Customer;
use App\Models\Program;
use App\Repositories\IProgramRepositories;
use App\Traits\ResponseTrait;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;

class ProgramController extends Controller
{
    use ResponseTrait ;
    protected  IProgramRepositories $programRepositories;
    public function __construct(IProgramRepositories $programRepositories)
    {
        $this->programRepositories = $programRepositories;
    }
    /**
     * Display a listing of the resource.
     */

    public function getAll(Request $request): \Illuminate\Http\JsonResponse
    {
        try {
            $limit = $request->get('limit', config('app.pagination_limit'))  ??  5 ;
            $programs = $this->programRepositories->paginateWithDetails($limit);
            return $this->successResponse(__('messages.DATA_RETRIEVED_SUCCESSFULLY'), ProgramResource::collection($programs), 200);
        } catch (\Exception $e) {
            return $this->errorResponse(__('messages.ERROR_OCCURRED'),['error' => $e->getMessage()],500);
        }
    }
    public function getAllProgramsForCurrentProvider(): \Illuminate\Http\JsonResponse
    {
        try {
            $user = auth()->user();
            if(!$user instanceof Customer){
                throw new \Exception('Get Current User  Failed');
            }
            $programs = $this->programRepositories->getWhereWith(['customer','videos'] ,['customer'=>$user->id]);
            return $this->successResponse(__('messages.DATA_RETRIEVED_SUCCESSFULLY'),  ProgramResource::collection($programs), 201);

        }catch (\Exception $e){
            return $this->errorResponse(__('messages.ERROR_OCCURRED'), ['error' => $e->getMessage()], 500);

        }
    }

    /**
     * Show the form for creating a new resource.
     */

}
