<?php

namespace App\Http\Controllers\Api\Device;

use App\Events\GloveDataUpdated;
use App\Http\Controllers\Controller;
use App\Http\Requests\api\device\StoreGloveDataRequest;
use App\Http\Requests\UpdateGloveDataRequest;
use App\Http\Resources\Api\Consultation\ConsultationResource;
use App\Http\Resources\Api\Device\GloveDeviceResource;
use App\Models\GloveData;
use App\Models\GloveDevice;
use App\Models\GloveError;
use App\Repositories\IGloveDataRepositories;
use App\Repositories\IGloveDeviceRepositories;
use App\Repositories\IGloveErrorRepositories;
use App\Traits\ResponseTrait;
use Illuminate\Support\Facades\DB;

class GloveDataController extends Controller
{
    use ResponseTrait;
    protected IGloveDataRepositories $gloveDataRepositories;
    protected IGloveDeviceRepositories $gloveDeviceRepositories;
    protected IGloveErrorRepositories $gloveErrorRepositories;

    public function __construct(IGloveErrorRepositories $gloveErrorRepositories ,IGloveDataRepositories $gloveDataRepositories , IGloveDeviceRepositories $gloveDeviceRepositories)
    {
        $this->gloveDataRepositories = $gloveDataRepositories;
        $this->gloveDeviceRepositories = $gloveDeviceRepositories;
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
    public function store(StoreGloveDataRequest $request): \Illuminate\Http\JsonResponse
    {
        try {
           DB::beginTransaction();
           if(!$request['glove_id'])
           {
               throw new \Exception('Device not paired: missing glove_id');
           }
            $data = $request->validated();


            $exists = GloveData::where('glove_id', $data['glove_id'])
                ->where('status', $data['status'])
                ->where('flex_thumb', $data['flex_thumb'])
                ->where('flex_index', $data['flex_index'])
                ->where('flex_middle', $data['flex_middle'])
                ->where('flex_ring', $data['flex_ring'])
                ->where('flex_pinky', $data['flex_pinky'])
                ->where('heartbeat', $data['heartbeat'])
                ->where('temperature', $data['temperature'])
                ->where('resistance', $data['resistance'])
                ->where('error_flag', $data['error_flag'])
                ->where('crc_valid', $data['crc_valid'])
                ->exists();
            if ($exists) {
                return response()->json([
                    'success' => true,
                    'message' => 'Duplicate data ignored',
                ]);
            }
            $gloveDevice = $this->gloveDeviceRepositories->findOrFail($request['glove_id']);
            if($gloveDevice->status != 'connected'){$gloveDevice->update(['status' => 'connected']);}
            if(!$gloveDevice->serial_number){ $gloveDevice->update(['serial_number' => $request['serial_number']]); };
            if(!$gloveDevice->smart_glove_id){ $gloveDevice->update(['smart_glove_id' => $request['device_id']]); };
            $vitalIndicatorsOfGlove = $this->gloveDataRepositories->create($request->validated());

            if($data['status'] != 4){event(new GloveDataUpdated($vitalIndicatorsOfGlove));}

            DB::commit();
            return $this->successResponse('Data received successfully',$vitalIndicatorsOfGlove, 202);
        } catch (\Exception $exception) {
            $this->gloveErrorRepositories->storeGloveError($exception->getMessage() ,$request['glove_id'] ,  null , GloveError::UNKNOWN  );
            DB::rollBack();
            return $this->errorResponse(__('messages.ERROR_OCCURRED'), ['error' => $exception->getMessage()], 500);
        }
    }






    /**
     * Display the specified resource.
     */
    public function show(GloveData $gloveData)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(GloveData $gloveData)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateGloveDataRequest $request, GloveData $gloveData)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(GloveData $gloveData)
    {
        //
    }
}
