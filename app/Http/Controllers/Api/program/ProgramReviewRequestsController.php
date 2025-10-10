<?php

namespace App\Http\Controllers\Api\program;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProgramReviewRequestsRequest;
use App\Http\Requests\UpdateProgramReviewRequestsRequest;
use App\Http\Resources\ProgramReviewResource;
use App\Models\ProgramReviewRequests;
use App\Repositories\IProgramReviewRequestsRepositories;
use App\Traits\ResponseTrait;

class ProgramReviewRequestsController extends Controller
{
    use ResponseTrait;
    protected IProgramReviewRequestsRepositories $programReviewRequestsRepositories;
    public function __construct(IProgramReviewRequestsRepositories $programReviewRequestsRepositories)
    {
        $this->programReviewRequestsRepositories = $programReviewRequestsRepositories;
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
    public function store(StoreProgramReviewRequestsRequest $request): \Illuminate\Http\JsonResponse
    {
        try {
            $programReview = $this->programReviewRequestsRepositories->create($request->validated());
            $programReview->load(['program','customer']);
            return $this->successResponse(__('messages.CREATE_SUCCESS'), new ProgramReviewResource($programReview), 201,);
        } catch (\Exception $exception) {
            return $this->errorResponse(__('messages.ERROR_OCCURRED'), ['error' => $exception->getMessage()], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(ProgramReviewRequests $programReviewRequests)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(ProgramReviewRequests $programReviewRequests)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateProgramReviewRequestsRequest $request, ProgramReviewRequests $programReviewRequests)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ProgramReviewRequests $programReviewRequests)
    {
        //
    }
}
