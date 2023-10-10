<?php

namespace App\Service\Provider\Paypal;

use App\Interface\IProviderAPI;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class Auth implements IProviderAPI
{
    private readonly string $url;
    private readonly string $clientId;
    private readonly string $clientSecret;

    public function __construct(
        private string $accountType,
        ParameterBagInterface $params
    ) {
        if ($params->get("app.paypal_sandbox"))
            $accountType = "sandbox";

        $this->url = $accountType === 'sandbox' ? $params->get("app.paypal_sandbox_api_url") : $params->get("app.paypal_api_url");
        $this->clientId = $params->get("app.paypal_account_$accountType");
        $this->clientSecret = $params->get(\sprintf("app.paypal_account_%s_secret", $accountType));
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function getClientId(): string
    {
        return $this->clientId;
    }

    public function getClientSecret(): string
    {
        return $this->clientSecret;
    }

    public function getToken(): string
    {
        $response = HttpClient::create()->request('POST', $this->url . '/v1/oauth2/token', [
            'headers' => [
                'Content-Type' => 'application/x-www-form-urlencoded',
                'Authorization' => 'Basic ' . base64_encode($this->clientId . ':' . $this->clientSecret),
            ],
            'body' => 'grant_type=client_credentials'
        ]);

        if ($response->getStatusCode() !== 200)
            throw new \UnexpectedValueException(\sprintf("Paypal get token action failed with response code %d with content %s", $response->getStatusCode(), $response->getContent()));

        $result = json_decode($response->getContent(), true);
        return $result['access_token'];
    }
}