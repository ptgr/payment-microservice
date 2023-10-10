<?php

namespace App\Service\Provider\Paypal;

use App\Interface\IProviderAPI;
use Symfony\Component\HttpClient\HttpClient;

class Api
{
    public function __construct(
        private IProviderAPI $providerApi
    ) {
    }

    public function createOrder(array $payload): array
    {
        $url = \sprintf("%s/v2/checkout/orders", $this->providerApi->getUrl());

        $response = HttpClient::create()->request('POST', $url, [
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $this->providerApi->getToken()
            ],
            'body' => json_encode($payload)
        ]);

        if ($response->getStatusCode() !== 200)
            throw new \UnexpectedValueException(\sprintf("Paypal provider url got response code %d with content %s", $response->getStatusCode(), $response->getContent()));

        return json_decode($response->getContent(), true);
    }

    public function captureOrder(string $token): array
    {
        $url = \sprintf("%s/v2/checkout/orders/%s/capture", $this->providerApi->getUrl(), $token);

        $response = HttpClient::create()->request('POST', $url, [
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $this->providerApi->getToken()
            ]
        ]);

        if ($response->getStatusCode() !== 201)
            throw new \UnexpectedValueException(\sprintf("Paypal capture failed with response code %d with content %s", $response->getStatusCode(), $response->getContent()));

        return json_decode($response->getContent(), true);
    }
}