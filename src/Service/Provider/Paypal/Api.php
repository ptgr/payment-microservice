<?php

namespace App\Service\Provider\Paypal;

use App\Interface\IPaypalAPI;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class Api implements IPaypalAPI
{
    private readonly string $url;
    private readonly string $clientId;
    private readonly string $clientSecret;

    public function __construct(
        private ParameterBagInterface $params
    ) {
    }

    public function setAccount(string $accountType): void
    {
        if ($this->params->get("app.paypal_sandbox"))
            $accountType = "sandbox";

        $this->url = $accountType === 'sandbox' ? $this->params->get("app.paypal_sandbox_api_url") : $this->params->get("app.paypal_api_url");
        $this->clientId = $this->params->get("app.paypal_account_$accountType");
        $this->clientSecret = $this->params->get(\sprintf("app.paypal_account_%s_secret", $accountType));
    }

    private function getToken(): string
    {
        if (empty($this->url) || empty($this->clientId) || empty($this->clientSecret))
            throw new \UnexpectedValueException('Paypal url, client id or secret is empty');

        $response = HttpClient::create()->request('POST', $this->url . '/v1/oauth2/token', [
            'headers' => [
                'Content-Type' => 'application/x-www-form-urlencoded',
                'Accept' => 'application/json'
            ],
            'auth_basic' => [$this->clientId, $this->clientSecret],
            'body' => 'grant_type=client_credentials'
        ]);

        if ($response->getStatusCode() !== 200)
            throw new \UnexpectedValueException(\sprintf("Paypal get token action failed with response code %d with content %s", $response->getStatusCode(), $response->getContent()));

        $result = $response->toArray();
        return $result['access_token'];
    }

    public function createOrder(array $payload): array
    {
        $url = \sprintf("%s/v2/checkout/orders", $this->url);

        $response = HttpClient::create()->request('POST', $url, [
            'headers' => [
                'Content-Type' => 'application/json',
                'Accept' => 'application/json'
            ],
            'auth_bearer' => $this->getToken(),
            'body' => json_encode($payload)
        ]);

        if ($response->getStatusCode() !== 200)
            throw new \UnexpectedValueException(\sprintf("Paypal provider url got response code %d with content %s", $response->getStatusCode(), $response->getContent()));

        return $response->toArray();
    }

    public function captureOrder(string $token): array
    {
        $url = \sprintf("%s/v2/checkout/orders/%s/capture", $this->url, $token);

        $response = HttpClient::create()->request('POST', $url, [
            'headers' => [
                'Content-Type' => 'application/json',
                'Accept' => 'application/json'
            ],
            'auth_bearer' => $this->getToken()
        ]);

        if ($response->getStatusCode() !== 201)
            throw new \UnexpectedValueException(\sprintf("Paypal capture failed with response code %d with content %s", $response->getStatusCode(), $response->getContent()));

        return $response->toArray();
    }
}