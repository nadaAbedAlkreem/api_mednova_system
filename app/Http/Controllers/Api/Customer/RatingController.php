<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\api\user\StoreRatingRequest;
use App\Http\Requests\UpdateRatingRequest;
use App\Http\Resources\Api\Customer\CustomerResource;
use App\Http\Resources\Api\Customer\RatingResource;
use App\Models\Rating;
use App\Repositories\ICustomerRepositories;
use App\Repositories\IRatingRepositories;
use App\Services\Api\Customer\RatingService;
use App\Traits\ResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class RatingController extends Controller
{
    use ResponseTrait;

    protected IRatingRepositories $ratingRepositories;
    protected ICustomerRepositories $customerRepositories;
    protected RatingService $ratingService;


    public function __construct(IRatingRepositories $ratingRepositories , RatingService $ratingService , ICustomerRepositories $customerRepositories)
    {
        $this->ratingRepositories = $ratingRepositories;
        $this->ratingService = $ratingService;
        $this->customerRepositories = $customerRepositories;
    }
    const REVIEWABLE_MODELS = [
        'customer' => \App\Models\Customer::class,
        'program' => \App\Models\Program::class,
//        'platform' => \App\Models\Platform::class,
    ];


    public function store(StoreRatingRequest $request): \Illuminate\Http\JsonResponse
    {
        try{
            $rating = $this->ratingRepositories->create($request->handle());
            $rating->load(['reviewer','reviewee']);
            return $this->successResponse(__('messages.CREATE_SUCCESS'), new RatingResource($rating), 201);
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
    public function getRatings(Request $request)
    {
        try {
            $request->validate([
                'reviewable_type' => [
                    'required',
                    Rule::in(array_keys(self::REVIEWABLE_MODELS))
                ],
                'reviewable_id' => ['required', 'integer'],
            ]);
            $modelClass = self::REVIEWABLE_MODELS[$request->reviewable_type];
            $model = $modelClass::findOrFail($request->reviewable_id);
            $ratings = $model->ratings()->latest()->paginate(10);
            $ratings->load(['reviewer','reviewee']);
            return $this->successResponse(
                'DATA_RETRIEVED_SUCCESSFULLY',
                RatingResource::collection($ratings),
                200,
                app()->getLocale()
            );

        }  catch (ModelNotFoundException $e) {
            return $this->errorResponse(
                __('messages.RESOURCE_NOT_FOUND'),
                null,
                404
            );

        } catch (\Exception $e) {
            return $this->errorResponse(
                'ERROR_OCCURRED',
                ['error' => $e->getMessage()],
                500
            );
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
