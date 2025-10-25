<?php
namespace App\Services\api;

use App\Events\ConsultationRequested;
use App\Models\ConsultationChatRequest;
use App\Models\Customer;
use App\Models\Rating;
use App\Models\User;
use App\Repositories\ICustomerRepositories;
use Exception;

class RatingService
{

    public function handle($request)
    {
        $typeServiceProvider = $request->query('typeServiceProvider') ?? 'therapist' ; // therapist | center
        $relation = ($typeServiceProvider === 'therapist')? 'therapist' : 'medicalSpecialties' ;
        $limit = $request->get('limit', config('app.pagination_limit'));
        $topRated = Rating::select('reviewee_id')
            ->selectRaw('AVG(rating) as average_rating, COUNT(*) as total_reviews')
            ->where('reviewee_type', $typeServiceProvider)
            ->groupBy('reviewee_id')
            ->orderByDesc('average_rating')
            ->with(['reviewee.'.$relation])
            ->paginate($limit);
        return $topRated->map(function ($rating) {
            $reviewee = $rating->reviewee;
            $reviewee->average_rating = $rating->average_rating;
            $reviewee->total_reviews = $rating->total_reviews;
            return $reviewee;
        });

    }





}
