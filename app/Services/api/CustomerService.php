<?php
namespace App\Services\api;

use App\Models\User;
use App\Repositories\ICustomerRepositories;
use Exception;

class CustomerService
{

    protected $customerRepository;

    public function __construct(ICustomerRepositories $customerRepository)
    {
        $this->customerRepository = $customerRepository; // Inject the repository
    }

    public function register($data)
    {
        try {
            $customer = $this->customerRepository->create($data);
            $customerToken =  $customer->createToken('API Token')->plainTextToken;
            return   [
                'access_token' =>  $customerToken ,
                'token_type' => 'Bearer',
                'user' => $customer
            ] ;
        } catch (\Exception $e) {
             throw new \Exception($e->getMessage());
        }

    }






}
