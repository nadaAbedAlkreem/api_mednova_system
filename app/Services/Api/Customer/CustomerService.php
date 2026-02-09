<?php
namespace App\Services\Api\Customer;

use App\Models\Customer;
use App\Repositories\ICustomerRepositories;

class CustomerService
{

    protected Customer $model;
    protected ICustomerRepositories $customerRepository;

    public function __construct(ICustomerRepositories $customerRepository , Customer $customer)
    {
        $this->customerRepository = $customerRepository;
        $this->model = $customer;

    }

    public function register($data): array
    {
        try {
            $customer = $this->customerRepository->create($data);
            $customerToken =  $customer->createToken('API Token')->plainTextToken;
            return [
                'access_token' =>  'Bearer '.$customerToken ,
                'customer' => $customer
            ] ;
        } catch (\Exception $e) {
             throw new \Exception($e->getMessage());
        }
    }

    public function getAll(array $filters = [], int $limit = 10)
    {
        $query = $this->model->query();

        // تجاهل المحظورين
        $query->where('is_banned', false);

        // فلترة حسب نوع الحساب
        if (!empty($filters['type_account'])) {
            $query->where('type_account', $filters['type_account']);
        }

        // فلترة حسب الحالة
        if (!empty($filters['approval_status'])) {
            $query->where('approval_status', $filters['approval_status']);
        }

        // فلترة حسب التحقق من الايميل
        if (!empty($filters['verified'])) {
            $query->whereNotNull('email_verified_at');
        }

        // البحث النصي
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function($q) use ($search) {
                $q->where('full_name', 'LIKE', "%{$search}%")
                    ->orWhere('email', 'LIKE', "%{$search}%")
                    ->orWhere('phone', 'LIKE', "%{$search}%");
            });
        }

        // الباجينيشن
        return $query->paginate($limit);
    }




}
