<?php

namespace App\Http\Controllers\Api\Program;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCustomerCourseProgressRequest;
use App\Http\Requests\UpdateCustomerCourseProgressRequest;
use App\Models\CustomerCourseProgress;

class CustomerCourseProgressController extends Controller
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
    public function store(StoreCustomerCourseProgressRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(CustomerCourseProgress $customerCourseProgress)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(CustomerCourseProgress $customerCourseProgress)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateCustomerCourseProgressRequest $request, CustomerCourseProgress $customerCourseProgress)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(CustomerCourseProgress $customerCourseProgress)
    {
        //
    }
}
