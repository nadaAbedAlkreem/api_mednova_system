<?php

namespace App\Repositories\Eloquent;


use App\Repositories\IOmnixSubscribeRepositories;
use Exception;
use Illuminate\Support\Facades\Http;


class OmnixSubscribeRepository  implements  IOmnixSubscribeRepositories
{
    protected string $baseUrl;
    protected string $accessToken;
    protected $httpClient;

    public function __construct($httpClient = null)
    {
        $this->baseUrl = config('omnix.api');
        $this->accessToken = config('omnix.access_token');
        $this->httpClient = $httpClient ?? Http::class;
    }


    public function subscribeCustomer($customer): ?string
    {
        try {
            $response = $this->httpClient->withHeaders($this->headers())
                ->post("{$this->baseUrl}/subscriber/create", $customer)
                ->throw()
                ->json();

            if (!empty($response['data']['user_ns'])) {
                return $response['data']['user_ns'];
            }

            return null;

        } catch (Exception $e) {
            return null;
        }
    }


    private function headers(): array
    {
        return [
            'Authorization' => 'Bearer ' . $this->accessToken,
            'Accept' => 'application/json',
        ];
    }
}
