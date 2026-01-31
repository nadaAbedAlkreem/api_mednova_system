<?php

namespace App\Http\Controllers\Api\Device;

use App\Http\Controllers\Controller;
use App\Http\Requests\api\device\StoreGloveCommandRequest;
use App\Models\GloveCommand;
use App\Models\GloveError;
use App\Repositories\IGloveErrorRepositories;
use App\Services\api\Glove\GloveCommandService;
use App\Traits\ResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class GloveCommandController extends Controller
{
    use ResponseTrait;

    protected $gloveCommandService;
    protected IGloveErrorRepositories $gloveErrorRepositories;

    public function __construct(GloveCommandService $gloveCommandService , IGloveErrorRepositories $gloveErrorRepositories)
    {
        $this->gloveCommandService = $gloveCommandService;
        $this->gloveErrorRepositories = $gloveErrorRepositories;
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
    public function sendCommand(StoreGloveCommandRequest $request): \Illuminate\Http\JsonResponse
    {
        try {
            DB::beginTransaction();
            $currentCustomer = auth('api')->user();
            if (!$currentCustomer) {throw new \Exception(__('messages.UNAUTHORISED'));}

            $responseData = $this->gloveCommandService->sendCommandToGlove($request->glove_command , $currentCustomer['id'] , $request['repeat']  ?? 1  , $request['rest_time'] ?? 60);

            DB::commit();
            return $this->successResponse('Command sent ', $responseData, 200);
        } catch (\Exception $exception) {
            DB::rollBack();
            return $this->errorResponse(__('messages.ERROR_OCCURRED'), [
                'error' => $exception->getMessage()
            ], 500);
        }
    }



    public function receiveResponseCommand(Request $request): \Illuminate\Http\JsonResponse
    {
        try {
            DB::beginTransaction();
                $validator = Validator::make($request->all(), [
                    'glove_id' => 'required|exists:glove_devices,id,deleted_at,NULL',
                    'command' => 'required|string|in:OPEN_HAND,PING,CLOSE_HAND,GRIP,RELAX,SET_FINGER,STOP,RESET',
                    'status' => 'required|string|in:failed,success',
                ]);

                if($validator->fails()) {
                    throw new \Exception(json_encode($validator->errors()->toArray()));
                }
               $command = GloveCommand::where('glove_id', $request['glove_id'])->where('command_code', $request['command'])->latest()->first();
               if($command) {
                $command->update([
                    'ack_status_device_response' => $request['status'],
                    'ack_received_device_response_at' =>  now(),
                ]);
                $command->load('glove');
                if($request['command'] == 'PING'){
                    if($command->glove)
                    {
                        $status = ($request['status'] == 'success')? 'connected' : 'error' ;
                        $command->glove->update(['status'=>$status, 'last_seen_at'=>now()]);
                    }
                }
                if ($request['status'] === 'failed') { // هنا مفروض اعدل على بياتات تمارين
                    $this->gloveErrorRepositories->storeGloveError('Glove reported operation failed: ' . ( $command['command_code']), $command['glove_id'],$command['id'] , GloveError::UNKNOWN);
                }
            }
             DB::commit();
            return $this->successResponse('Command response received successfully', [], 200);
        } catch (\Exception $exception) {
            $this->gloveErrorRepositories->storeGloveError('Glove reported operation failed: ' . ($exception->getMessage() ?? 'Unknown error') ,$command['glove_id'],$command['id'] , GloveError::UNKNOWN);
            DB::rollBack();
            return $this->errorResponse(__('messages.ERROR_OCCURRED'), ['error' => $exception->getMessage()], 500);
        }
    }




}
