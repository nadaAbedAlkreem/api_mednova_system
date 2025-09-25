<?php

namespace App\Http\Controllers\Dashboard\order;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreOrderNotificationRequest;
use App\Http\Requests\UpdateOrderNotificationRequest;
use App\Models\OrderNotification;
use App\Repositories\IOrderNotificationRepositories;
use App\Services\dashborad\order\OrderNotificationDatatableService;
use App\Traits\ResponseTrait;
use Illuminate\Http\Request;
use Throwable;

class OrderNotificationController extends Controller
{
    use ResponseTrait ;
    protected $orderNotificationRepositories ;

    public function __construct(IOrderNotificationRepositories $orderNotificationRepositories )
    {
        $this->orderNotificationRepositories = $orderNotificationRepositories;

    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request ,OrderNotificationDatatableService $orderNotificationDatatableService)
    {
        if ($request->ajax())
        {
            $orderNotification = $this->orderNotificationRepositories->getAllWithout();
            try {
                return $orderNotificationDatatableService->handle($request,$orderNotification );
            } catch (Throwable $e) {
                return response([
                    'message' => $e->getMessage(),
                ], 500);
            }
        }
        return view('notification.index');

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
    public function store(StoreOrderNotificationRequest $request)
    {
        try {
             $this->orderNotificationRepositories->create($request->validated());
            return $this->successResponse('CREATE_SUCCESS', [], 201,);
        } catch (\Exception $e) {
            return response([
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(OrderNotification $orderNotification)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(OrderNotification $orderNotification)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateOrderNotificationRequest $request, OrderNotification $orderNotification)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(OrderNotification $orderNotification)
    {
        //
    }
}
