<?php

namespace App\Http\Controllers\Api\Payment;

use App\Http\Controllers\Controller;
use App\Http\Requests\api\payment\StoreGatewayPaymentRequest;
use App\Http\Requests\api\payment\UpdateGatewayPaymentRequest;
use App\Models\GatewayPayment;

class GatewayPaymentController extends Controller
{
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
    public function store(StoreGatewayPaymentRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(GatewayPayment $gatewayPayment)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(GatewayPayment $gatewayPayment)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateGatewayPaymentRequest $request, GatewayPayment $gatewayPayment)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(GatewayPayment $gatewayPayment)
    {
        //
    }
}
