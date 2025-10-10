<?php

namespace App\Http\Controllers\Api\program;

use App\Http\Controllers\Controller;
use App\Http\Requests\api\program\StoreProgramVideosRequest;
use App\Http\Requests\api\program\UpdateProgramVideosRequest;
use App\Http\Resources\ProgramResource;
use App\Http\Resources\VideoResource;
use App\Models\Program;
use App\Models\ProgramVideos;
use App\Repositories\IProgramVideosRepositories;
use App\Traits\ResponseTrait;
use Illuminate\Support\Facades\DB;

class ProgramVideosController extends Controller
{
    use ResponseTrait;
    protected IProgramVideosRepositories $programVideosRepositories;
    public function __construct(IProgramVideosRepositories $programVideosRepositories)
    {
        $this->programVideosRepositories = $programVideosRepositories;
    }
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
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
    public function store(StoreProgramVideosRequest $request)
    {
        try {
            DB::beginTransaction();
            $data = $request->getData();
            $programId = $data['program_id'];
            if (isset($data['videos']) && is_array($data['videos'])) {
                foreach ($data['videos'] as $videoData) {
                    $videoData['program_id'] =$programId;
                    $this->programVideosRepositories->create($videoData);
                }
            }
            $program = Program::with('videos')->find($programId);
            DB::commit();
            return $this->successResponse(__('messages.CREATE_SUCCESS'), new ProgramResource($program), 201,);
        } catch (\Exception $exception) {
            DB::rollBack();
            return $this->errorResponse(__('messages.ERROR_OCCURRED'), ['error' => $exception->getMessage()], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(ProgramVideos $programVideos)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(ProgramVideos $programVideos)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateProgramVideosRequest $request): \Illuminate\Http\JsonResponse
    {
        try {
            $video =$this->programVideosRepositories->update($request->getData() , $request['video_id']);
            return $this->successResponse(__('messages.UPDATE_SUCCESS'), new VideoResource($video), 201,);
        }catch (\Exception $exception){
            return $this->errorResponse(__('messages.ERROR_OCCURRED'), ['error' => $exception->getMessage()], 500);

        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($videoId)
    {
        try {
            $this->programVideosRepositories->delete($videoId);
            return $this->successResponse(__('messages.DELETE_SUCCESS'), [], 202,);
        } catch (\Exception $exception) {
            return $this->errorResponse(__('messages.ERROR_OCCURRED'), ['error' => $exception->getMessage()], 500);
        }
    }
}
