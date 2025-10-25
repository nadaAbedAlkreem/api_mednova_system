<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\api\user\StoreRatingRequest;
use App\Http\Requests\UpdateRatingRequest;
use App\Http\Resources\Api\Customer\CustomerResource;
use App\Http\Resources\Api\Customer\RatingResource;
use App\Models\Rating;
use App\Repositories\IConsultationChatRequestRepositories;
use App\Repositories\IRatingRepositories;
use App\Services\api\ConsultationStatusService;
use App\Services\api\RatingService;
use App\Traits\ResponseTrait;
use Illuminate\Http\Request;

class RatingController extends Controller
{
    use ResponseTrait;

    protected IRatingRepositories $ratingRepositories;
    protected RatingService $ratingService;

    public function __construct(IRatingRepositories $ratingRepositories , RatingService $ratingService)
    {
        $this->ratingRepositories = $ratingRepositories;
        $this->ratingService = $ratingService;
    }

    public function store(StoreRatingRequest $request): \Illuminate\Http\JsonResponse
    {
        try{
            $rating = $this->ratingRepositories->create($request->validated());
            $rating->load(['reviewer','reviewee']);
            return $this->successResponse(__('messages.CREATE_SUCCESS'), new RatingResource($rating), 201,);
        }catch (\Exception $exception){
            return $this->errorResponse(__('messages.ERROR_OCCURRED'), ['error' => $exception->getMessage()], 500, app()->getLocale());
        }
    }

   public function getTopRatedServiceProvider(Request $request): \Illuminate\Http\JsonResponse
   {
      try {
            $reviewees = $this->ratingService->handle($request);
             return $this->successResponse('DATA_RETRIEVED_SUCCESSFULLY', CustomerResource::collection($reviewees) , 202, app()->getLocale());
        }catch (\Exception $exception){
            return $this->errorResponse('ERROR_OCCURRED', ['error' => $exception->getMessage()], 500);
        }
   }





    /**
     * Display the specified resource.
     */
    public function show(Rating $rating)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Rating $rating)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateRatingRequest $request, Rating $rating)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Rating $rating)
    {
        //
    }
}
