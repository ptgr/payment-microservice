<?php

namespace App\Tests\Application\Paypal;

use App\Interface\IPaypalAPI;
use App\Service\Provider\Paypal\Api;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class RedirectToProviderTest extends WebTestCase
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

    public function testRedirectToProvider(): void
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
        
        // Reboot kernel otherwise mocking does not work
        $this->tearDown();
        $this->setUp();

        $paypalApiMock = $this->createMock(IPaypalAPI::class);
        $paypalApiMock->expects($this->once())->method('createOrder')->willReturn(['links' => [['rel' => 'payer-action', 'href' => 'https://www.redirect-to-paypal.com']]]);
        self::$kernel->getContainer()->set(Api::class, $paypalApiMock);

        $this->client->request('GET', '/api/v1/payment/pay/' . $arrayResponse['token']);
        $response = $this->client->getResponse();

        $this->assertSame(302, $response->getStatusCode());
        $this->assertSame('https://www.redirect-to-paypal.com', $response->getTargetUrl());
    }

    public function testRedirectWithNoLinks(): void
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
        
        // Reboot kernel otherwise mocking does not work
        $this->tearDown();
        $this->setUp();

        $paypalApiMock = $this->createMock(IPaypalAPI::class);
        $paypalApiMock->expects($this->once())->method('createOrder')->willReturn(['links' => [['rel' => 'payerrrrrrr-action', 'href' => 'https://www.redirect-to-paypal.com']]]);
        self::$kernel->getContainer()->set(Api::class, $paypalApiMock);

        $this->client->request('GET', '/api/v1/payment/pay/' . $arrayResponse['token']);
        $response = $this->client->getResponse();

        $this->assertSame(302, $response->getStatusCode());
        $this->assertSame('http://localhost', $response->getTargetUrl());
    }
}