<?php
namespace App\Services\Api\Customer;

use App\Repositories\ICustomerRepositories;

class CustomerService
{

    protected ICustomerRepositories $customerRepository;

    public function __construct(ICustomerRepositories $customerRepository)
    {
        $this->customerRepository = $customerRepository;
    }

    public function register($data): array
    {
        try {
            $customer = $this->customerRepository->create($data);
            $customerToken =  $customer->createToken('API Token')->plainTextToken;
            return   [
                'access_token' =>  'Bearer '.$customerToken ,
                'customer' => $customer
            ] ;
        } catch (\Exception $e) {
             throw new \Exception($e->getMessage());
        }

    }





}
