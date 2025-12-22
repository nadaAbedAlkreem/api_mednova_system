<?php

namespace App\Http\Controllers\Api\Payment;

use App\Http\Controllers\Controller;
use App\Http\Requests\api\payment\StorebankAccountRequest;
use App\Http\Requests\api\payment\UpdatebankAccountRequest;
use App\Models\bankAccount;

class BankAccountController extends Controller
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
    public function store(StorebankAccountRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(bankAccount $bankAccount)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(bankAccount $bankAccount)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdatebankAccountRequest $request, bankAccount $bankAccount)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(bankAccount $bankAccount)
    {
        //
    }
}
