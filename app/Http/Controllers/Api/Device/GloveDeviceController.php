<?php

namespace App\Http\Controllers\Api\Device;

use App\Http\Controllers\Controller;
use App\Http\Requests\api\device\StoreGloveDeviceRequest;
use App\Http\Requests\UpdateGloveDeviceRequest;
use App\Http\Resources\Api\Consultation\ConsultationResource;
use App\Models\GloveCommand;
use App\Models\GloveDevice;
use App\Repositories\IGloveCommandRepositories;
use App\Repositories\IGloveDeviceRepositories;
use App\Traits\ResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GloveDeviceController extends Controller
{
    use ResponseTrait;
    protected IGloveDeviceRepositories $gloveDeviceRepositories;
    protected IGloveCommandRepositories $gloveCommandRepositories;
    public function __construct(IGloveDeviceRepositories $gloveDeviceRepositories , IGloveCommandRepositories $gloveCommandRepositories)
    {
        $this->gloveDeviceRepositories = $gloveDeviceRepositories;
        $this->gloveCommandRepositories = $gloveCommandRepositories;
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


    /**
     * Display the specified resource.
     */
    public function show(GloveDevice $gloveDevice)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(GloveDevice $gloveDevice)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateGloveDeviceRequest $request, GloveDevice $gloveDevice)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(GloveDevice $gloveDevice)
    {
        //
    }
}
