<?php

namespace App\Http\Controllers\Api\Device;

use App\Http\Controllers\Controller;
use App\Http\Requests\api\device\StoreGloveErrorRequest;
use App\Models\GloveError;
use App\Repositories\IGloveErrorRepositories;
use App\Traits\ResponseTrait;
use Illuminate\Http\Request;

class GloveErrorController extends Controller
{
    use ResponseTrait;
    protected IGloveErrorRepositories $gloveErrorRepo;
    public function __construct(IGloveErrorRepositories $gloveErrorRepo)
    {
        $this->gloveErrorRepo = $gloveErrorRepo;
    }

    public function receiveErrorReport(StoreGloveErrorRequest $request): \Illuminate\Http\JsonResponse
    {
        try {
            $this->gloveErrorRepo->storeGloveError($request->error_message, $request->glove_id, $request->command_id ?? null, $request->error_type ?? GloveError::UNKNOWN);
            return $this->successResponse('Store error glove successfully', [], 200);
        }catch (\Exception $exception){
            return $this->errorResponse(__('messages.ERROR_OCCURRED'), ['error' => $exception->getMessage()], 500);
        }
    }

}
