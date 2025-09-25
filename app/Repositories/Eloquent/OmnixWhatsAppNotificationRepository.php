<?php

namespace App\Repositories\Eloquent;

use App\Models\Customer;
use App\Repositories\IOmnixNotificationRepositories;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;

class OmnixWhatsAppNotificationRepository implements IOmnixNotificationRepositories
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
    public function send(Customer $customer, string $template, array $params = []): array
    {
        $payload = $this->preparePayload($customer, $template, $params);

        $response = $this->httpClient::withHeaders($this->headers())
            ->post("{$this->baseUrl}/send-whatsapp-template-by-user-id", $payload)
            ->throw()
            ->json();

        return $response;
    }

    private function preparePayload(Customer $customer, string $template, array $params): array
    {
        return [
            'user_id' => $customer->omnix_user_id,
            'content' => [
                'name' => $template,
                'lang' => 'en',
                'params' => $params
            ]
        ];
    }

    private function headers(): array
    {
        return [
            'Authorization' => 'Bearer ' . $this->accessToken,
            'Accept' => 'application/json',
        ];
    }
}
