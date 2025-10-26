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
        $typeServiceProvider = $request->query('typeServiceProvider') ?? 'therapist' ; // therapist | rehabilitation_center
        $relation = ($typeServiceProvider === 'therapist')? ['reviewee.therapist.specialty'] : ['reviewee.medicalSpecialties' , 'reviewee.rehabilitationCenter'] ;
        $limit = $request->get('limit', config('app.pagination_limit'));
        $topRated = Rating::select('reviewee_id', 'reviewee_type')
            ->selectRaw('AVG(rating) as average_rating, COUNT(*) as total_reviews')
            ->where('reviewee_type', Customer::class)
            ->whereHasMorph(
                'reviewee',
                [Customer::class],
                function ($q) use ($typeServiceProvider) {
                    $q->where('type_account', $typeServiceProvider);
                }
            )
            ->groupBy('reviewee_id', 'reviewee_type')
            ->orderByDesc('average_rating')
            ->with($relation)
            ->paginate($limit);
         return $topRated->map(function ($rating) {
             $reviewee = $rating->reviewee;
            $reviewee->average_rating = $rating->average_rating;
            $reviewee->total_reviews = $rating->total_reviews;
            return $reviewee;
        });

    }





}
