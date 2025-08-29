<?php

namespace App\Http\Controllers\Dashborad;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreOrderRequest;
use App\Http\Requests\UpdateOrderRequest;
use App\Models\Order;
use App\Repositories\IOrderRepositories;
use App\Services\OrderDatatableService;
use App\Traits\ResponseTrait;
use Illuminate\Http\Request;
use Throwable;

class OrderController extends Controller
{

    use ResponseTrait ;
    protected $orderRepositories ;

    public function __construct(IOrderRepositories $orderRepositories )
    {
         $this->orderRepositories = $orderRepositories;

    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request ,OrderDatatableService $orderDatatableService)
    {
         if ($request->ajax())
        {
            $order = $this->orderRepositories->getAllWithout();
            try {
                return $orderDatatableService->handle($request,$order );
            } catch (Throwable $e) {
                return response([
                    'message' => $e->getMessage(),
                ], 500);
            }
        }
        return view('order.index');

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
    public function store(StoreOrderRequest $request)
    {
        try {
            $this->orderRepositories->create($request->validated());
            return $this->successResponse('ORDER_CREATE_SUCCESS', [], 202,app()->getLocale());
        } catch (\Exception $e) {
            return $this->errorResponse('ERROR_OCCURRED', ['error' => $e->getMessage()], 500, app()->getLocale());
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Order $order)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Order $order)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateOrderRequest $request, Order $order)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Order $order)
    {
        //
    }
}
