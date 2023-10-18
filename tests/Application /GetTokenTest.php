<?php

namespace App\Tests\Application;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class GetTokenTest extends WebTestCase
{
    private ?\Doctrine\ORM\EntityManager $entityManager;
    private \Symfony\Bundle\FrameworkBundle\KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->entityManager = static::$kernel->getContainer()->get('doctrine')->getManager();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        // doing this is recommended to avoid memory leaks
        $this->entityManager->close();
        $this->entityManager = null;
    }

    /**
     * @dataProvider requestProvider
     */
    public function testRequestValidation(string $payload, int $responseCode): void
    {
        $this->client->request('POST', '/api/v1/payment/token', server: [
            'headers' => [
                'Content-Type' => 'application/json',
                'Accept' => 'application/json'
            ],
        ], content: $payload
        );

        $response = $this->client->getResponse();
        $this->assertSame($responseCode, $response->getStatusCode());
    }

    public function requestProvider(): array
    {
        return [
            'missing_body' => ['', 400],
            'missing_transaction_name' => ['{"vat":20,"currency_code":"EUR","provider_id":1,"items":[{"external_id":1001,"name":"Laptop","quantity":5,"price":80,"discount":10,"shipping":100}]}', 400],
            'empty_transaction_name' => ['{"transaction_name":"","vat":20,"currency_code":"EUR","provider_id":1,"items":[{"external_id":1001,"name":"Laptop","quantity":5,"price":80,"discount":10,"shipping":100}]}', 400],
            'negative_vat' => ['{"transaction_name":"TR23FDF64564","vat":-20,"currency_code":"EUR","provider_id":1,"items":[{"external_id":1001,"name":"Laptop","quantity":5,"price":80,"discount":10,"shipping":100}]}', 400],
            'invalid_currency' => ['{"transaction_name":"TR23FDF64564","vat":20,"currency_code":"EURRRR","provider_id":1,"items":[{"external_id":1001,"name":"Laptop","quantity":5,"price":80,"discount":10,"shipping":100}]}', 400],
            'provider_wrong_type' => ['{"transaction_name":"TR23FDF64564","vat":20,"currency_code":"EUR","provider_id":"test","items":[{"external_id":1001,"name":"Laptop","quantity":5,"price":80,"discount":10,"shipping":100}]}', 400],
            'no_items' => ['{"transaction_name":"TR23FDF64564","vat":20,"currency_code":"EUR","provider_id":1,"items":[]}', 400],
            'negative_external_id' => ['{"transaction_name":"TR23FDF64564","vat":20,"currency_code":"EUR","provider_id":1,"items":[{"external_id":-1001,"name":"Laptop","quantity":5,"price":80,"discount":10,"shipping":100}]}', 400],
            'empty_name' => ['{"transaction_name":"TR23FDF64564","vat":20,"currency_code":"EUR","provider_id":1,"items":[{"external_id":1001,"name":"","quantity":5,"price":80,"discount":10,"shipping":100}]}', 400],
            'zero_quantity' => ['{"transaction_name":"TR23FDF64564","vat":20,"currency_code":"EUR","provider_id":1,"items":[{"external_id":1001,"name":"Laptop","quantity":0,"price":80,"discount":10,"shipping":100}]}', 400],
            'price_as_text' => ['{"transaction_name":"TR23FDF64564","vat":20,"currency_code":"EUR","provider_id":1,"items":[{"external_id":1001,"name":"Laptop","quantity":5,"price":"fsdfds","discount":10,"shipping":100}]}', 400],
            'negative_discount' => ['{"transaction_name":"TR23FDF64564","vat":20,"currency_code":"EUR","provider_id":1,"items":[{"external_id":1001,"name":"Laptop","quantity":5,"price":80,"discount":-10,"shipping":100}]}', 400],
            'negative_shipping' => ['{"transaction_name":"TR23FDF64564","vat":20,"currency_code":"EUR","provider_id":1,"items":[{"external_id":1001,"name":"Laptop","quantity":5,"price":80,"discount":10,"shipping":-100}]}', 400],
            
            'valid_vat_zero' => ['{"transaction_name":"TR23FDF64564","vat":0,"currency_code":"EUR","provider_id":5,"items":[{"external_id":1001,"name":"Laptop","quantity":5,"price":80,"discount":10,"shipping":100}]}', 422],
            'valid_decimal_price' => ['{"transaction_name":"TR23FDF64564","vat":0,"currency_code":"EUR","provider_id":5,"items":[{"external_id":1001,"name":"Laptop","quantity":5,"price":80.89,"discount":10,"shipping":100}]}', 422],
            'valid_discount_zero' => ['{"transaction_name":"TR23FDF64564","vat":0,"currency_code":"EUR","provider_id":5,"items":[{"external_id":1001,"name":"Laptop","quantity":5,"price":80.89,"discount":0,"shipping":100}]}', 422],
            'valid_shipping_zero' => ['{"transaction_name":"TR23FDF64564","vat":0,"currency_code":"EUR","provider_id":5,"items":[{"external_id":1001,"name":"Laptop","quantity":5,"price":80.89,"discount":0,"shipping":0}]}', 422],
        ];
    }

    public function testNoProvider(): void
    {
        $this->client->request('POST', '/api/v1/payment/token', server: [
            'headers' => [
                'Content-Type' => 'application/json',
                'Accept' => 'application/json'
            ],
        ], content: '{"transaction_name":"TR23FDF64564","vat":20,"currency_code":"EUR","provider_id":5,"items":[{"external_id":1001,"name":"Laptop","quantity":5,"price":80,"discount":10,"shipping":100}]}'
        );

        $response = $this->client->getResponse();
        $this->assertSame(422, $response->getStatusCode());
    }

    public function testGetToken(): void
    {
        $this->client->request('POST', '/api/v1/payment/token', server: [
            'headers' => [
                'Content-Type' => 'application/json',
                'Accept' => 'application/json'
            ],
        ], content: '{"transaction_name":"TR23FDF64564","vat":20,"currency_code":"EUR","provider_id":1,"items":[{"external_id":1001,"name":"Laptop","quantity":5,"price":80,"discount":10,"shipping":100}]}'
        );

        $response = $this->client->getResponse();
        $arrayResponse = \json_decode($response->getContent(), true);

        $this->assertSame(201, $response->getStatusCode());
        $this->assertNotEmpty($arrayResponse['token']);
        $this->assertNotEmpty($arrayResponse['pay_url']);
    }
}