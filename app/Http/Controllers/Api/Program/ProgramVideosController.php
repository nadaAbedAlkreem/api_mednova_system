<?php

namespace App\Http\Controllers\Api\Program;

use App\Http\Controllers\Controller;
use App\Http\Requests\api\program\StoreProgramVideosRequest;
use App\Http\Requests\api\program\UpdateProgramVideosRequest;
use App\Http\Resources\Api\Program\ProgramResource;
use App\Http\Resources\Api\Program\VideoResource;
use App\Models\Program;
use App\Models\ProgramVideos;
use App\Repositories\IProgramVideosRepositories;
use App\Services\Api\Customer\UploadService;
use App\Traits\ResponseTrait;
use Illuminate\Database\Eloquent\ModelNotFoundException;
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
    public function show($programVideos)
    {
        try {
            $video = $this->programVideosRepositories->findOne($programVideos);
            if (!$video) {
                return $this->errorResponse(__('messages.VIDEO_NOT_FOUND'), [], 404);
            }
            return $this->successResponse(__('messages.DATA_RETRIEVED_SUCCESSFULLY'), new VideoResource($video), 200);
        } catch (\Exception $e) {
            return $this->errorResponse(__('messages.ERROR_OCCURRED'), ['error' => $e->getMessage()], 500);
        }
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
    public function update(UpdateProgramVideosRequest $request, ProgramVideos $video)
    {
        try {
            $data = $request->validated();
            if ($request->hasFile('video_path')) {
                $uploadService = new UploadService();
                $path = $uploadService->upload($request->file('video_path'), 'program_videos', 'public', 'videos');
                $data['video_path'] = asset('storage/' . $path);}
                $video->update($data);
            return response()->json(['success' => true, 'message' => __('messages.UPDATE_SUCCESS'), 'data' => $video->fresh()]);
        } catch (\Exception $exception) {
            return response()->json(['success' => false, 'message' => __('messages.ERROR_OCCURRED'), 'error' => $exception->getMessage()], 500);
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
        } catch (ModelNotFoundException $e) {
            return $this->errorResponse(__('messages.VIDEO_NOT_FOUND'), [], 404);
        }
        catch (\Exception $exception) {
            return $this->errorResponse(__('messages.ERROR_OCCURRED'), ['error' => $exception->getMessage()], 500);
        }
    }
}
