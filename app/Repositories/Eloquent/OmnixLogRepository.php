<?php

namespace App\Repositories\Eloquent;


use App\Models\OmnixLog;
use App\Repositories\IOmnixLogRepositories;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;


class OmnixLogRepository  implements  IOmnixLogRepositories
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
                Log::info("Omnix: تم الاشتراك بنجاح", [
                    'customer_id' => $customer->id,
                    'user_ns' => $response['data']['user_ns']
                ]);
                return $response['data']['user_ns'];
            }

            Log::warning("Omnix: الاشتراك فشل بدون خطأ", ['customer_id' => $customer->id]);
            return null;

        } catch (Exception $e) {
            Log::error("Omnix: خطأ أثناء الاشتراك", [
                'customer_id' => $customer->id,
                'error' => $e->getMessage()
            ]);
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
