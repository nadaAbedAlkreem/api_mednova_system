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
            $limit = $request->get('limit', config('app.pagination_limit'));
            $programs = Program::with( ['creator'])->withAvg('ratings', 'rating')->withCount('ratings')->withCount('enrollments')->orderBy( 'id',  'DESC')->paginate($limit);
//            $programs = $this->programRepositories->paginateWhereWith(['is_approved' => 1], ['creator'], ['column' => 'id', 'dir' => 'DESC'], $limit);
            return $this->successResponse(__('messages.DATA_RETRIEVED_SUCCESSFULLY'),  ProgramResource::collection($programs), 201);

        }catch (\Exception $e){
            return $this->errorResponse(__('messages.ERROR_OCCURRED'), ['error' => $e->getMessage()], 500);

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
    public function publish($id): \Illuminate\Http\JsonResponse
    {
        try {
           $program = $this->programRepositories->findOrFail($id);
              if(!$program->is_approved)
              {
                  return $this->errorResponse(__('messages.NOT_APPROVED'), [], 422);
              }
           $program->status = 'published';
           $program->save();
            return $this->successResponse(__('messages.SUCCESS_PUBLISHED'),  new ProgramResource($program), 201);

         }catch (\Exception $e){
            return $this->errorResponse(__('messages.ERROR_OCCURRED'), ['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreProgramRequest $request): \Illuminate\Http\JsonResponse
    {
        try {
            $program = $this->programRepositories->create($request->getData());
            $program->load('creator');
             return $this->successResponse(__('messages.CREATE_SUCCESS'), new ProgramResource($program), 201);
        } catch (\Exception $exception) {
            return $this->errorResponse(__('messages.ERROR_OCCURRED'), ['error' => $exception->getMessage()], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show($programId): \Illuminate\Http\JsonResponse
    {
        try {
            $program = $this->programRepositories->findOne($programId);
            if (! $program) {return $this->errorResponse(__('messages.PROGRAM_NOT_FOUND'), [], 404);}
            $program = $program->loadCount('ratings', 'enrollments')
                ->loadAvg('ratings', 'rating')
                ->load(['creator', 'videos']);
            return $this->successResponse(__('messages.DATA_RETRIEVED_SUCCESSFULLY'), new ProgramResource($program), 201);
        }catch (\Exception $exception)
        {
            return $this->errorResponse(__('messages.ERROR_OCCURRED'), ['error' => $exception->getMessage()], 500);

        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Program $program)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateProgramRequest $request): \Illuminate\Http\JsonResponse
    {
        try {
            $program = $this->programRepositories->update($request->getData(), $request['program_id']);
            return $this->successResponse(__('messages.UPDATE_SUCCESS'), [], 201);
        } catch (\Exception $exception) {
            return $this->errorResponse(__('messages.ERROR_OCCURRED'), ['error' => $exception->getMessage()], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id): \Illuminate\Http\JsonResponse
    {
        try {
            $this->programRepositories->delete($id);
            return $this->successResponse(__('messages.DELETE_SUCCESS'), [], 202);
        } catch (ModelNotFoundException $e) {
                return $this->errorResponse(__('messages.PROGRAM_NOT_FOUND'), [], 404);
        } catch (\Exception $exception) {
            return $this->errorResponse(__('messages.ERROR_OCCURRED'), ['error' => $exception->getMessage()], 500);
        }
    }
}
