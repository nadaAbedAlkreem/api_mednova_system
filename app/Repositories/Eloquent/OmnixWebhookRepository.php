<?php

namespace App\Repositories\Eloquent;

use App\Models\Customer;
use App\Repositories\IOmnixNotificationRepositories;
use App\Repositories\IOmnixWebhookRepositories;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;

class OmnixWebhookRepository implements IOmnixWebhookRepositories
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

    /**
     * @throws RequestException
     * @throws ConnectionException
     */
    public function getInboundWebhooks(): array
    {
        $response = $this->httpClient::withHeaders($this->headers())
            ->get("{$this->baseUrl}/flow/inbound-webhooks")
            ->throw()
            ->json();

        return $response;
    }



    private function headers(): array
    {
        return [
            'Authorization' => 'Bearer ' . $this->accessToken,
            'Accept' => 'application/json',
        ];
    }
}
