<?php
namespace App\Services\api;

use App\Models\Customer;
use App\Models\User;
use App\Repositories\ICustomerRepositories;
use Exception;

class SearchServiceProviderService
{



    public function searchServiceProviders($filters): \Illuminate\Pagination\LengthAwarePaginator
    {
        $query = Customer::query();
        $limit = $filters['limit'] ?? 5;

        // البحث حسب نوع الحساب
        if (!empty($filters['type'])) {
            $type = $filters['type'];
            if ($type === 'therapist') {
                $query->where('type_account', 'therapist')
                    ->when($filters['full_name'] ?? null, function ($q, $name) {
                        $q->where('full_name', 'like', "%{$name}%");
                    })
                    ->when($filters['specialty_id'] ?? null, function ($q, $specialtyId) {
                        $q->whereHas('therapist', function ($q2) use ($specialtyId) {
                            $q2->where('medical_specialties_id', $specialtyId);
                        });
                    });
            }

            elseif ($type === 'center') {
                $query->where('type_account', 'rehabilitation_center')
                    ->when($filters['full_name'] ?? null, function ($q, $name) {
                        $q->where('full_name', 'like', "%{$name}%");
                    })
                    ->when($filters['specialty_id'] ?? null, function ($q, $specialtyId) {
                        $q->whereHas('medicalSpecialties', function ($q2) use ($specialtyId) {
                            $q2->where('specialty_id', $specialtyId);
                        });
                    });
            }
        } else {
            // البحث على الاسم إذا لم يتم تحديد النوع
            if (!empty($filters['full_name'])) {
                $query->where('full_name', 'like', "%{$filters['full_name']}%");
            }
        }

        // البحث حسب الموقع
        if (!empty($filters['country']) || !empty($filters['city'])) {
            $query->whereHas('location', function ($q) use ($filters) {
                if (!empty($filters['country'])) {
                    $q->where('country', 'like', "%{$filters['country']}%");
                }
                if (!empty($filters['city'])) {
                    $q->where('city', 'like', "%{$filters['city']}%");
                }
            });
        }
        $query->withAvg('ratings as average_rating', 'rating')
            ->withCount('ratings as total_reviews')
            ->with(['location', 'therapist','therapist.specialty', 'rehabilitationCenter', 'medicalSpecialties']);

        return $query->paginate($limit ?? 10);
    }




}
