<?php

namespace App\Tests\Application\Paypal;

use App\Entity\Payment;
use App\Enum\PaymentStatus;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class NotifyTest extends WebTestCase
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

    public function testComplete(): void
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

        $customId = \base64_encode($arrayResponse['token'] . 'TR23FDF64564');
        $this->client->request('POST', '/api/v1/payment/notify', server: [
            'headers' => [
                'Content-Type' => 'application/json'
            ],
        ], content: '{"event_type": "PAYMENT.CAPTURE.COMPLETED", "resource":{"custom_id":"' . $customId . '","id":"12A34567BC123456S","amount":{"currency_code":"USD","value":"30.00"}}}'
        );

        $response = $this->client->getResponse();
        $paymentEntity = $this->entityManager->getRepository(Payment::class)->findOneBy(['token' => $arrayResponse['token']]);
        
        $this->assertSame(200, $response->getStatusCode());
        $this->assertEquals(PaymentStatus::CAPTURED->value, $paymentEntity->getStatus()->value);
    }

    public function testCompleteWithTokenInUrl(): void
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

        $this->client->request('POST', '/api/v1/payment/notify/' . $arrayResponse['token'], server: [
            'headers' => [
                'Content-Type' => 'application/json'
            ],
        ], content: '{"event_type": "PAYMENT.CAPTURE.COMPLETED", "resource":{"id":"12A34567BC123456S","amount":{"currency_code":"USD","value":"30.00"}}}'
        );

        $response = $this->client->getResponse();
        $paymentEntity = $this->entityManager->getRepository(Payment::class)->findOneBy(['token' => $arrayResponse['token']]);
        
        $this->assertSame(200, $response->getStatusCode());
        $this->assertEquals(PaymentStatus::CAPTURED->value, $paymentEntity->getStatus()->value);
    }

    public function testCompleteThenRefund(): void
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

        $customId = \base64_encode($arrayResponse['token'] . 'TR23FDF64564');
        $this->client->request('POST', '/api/v1/payment/notify', server: [
            'headers' => [
                'Content-Type' => 'application/json'
            ],
        ], content: '{"event_type": "PAYMENT.CAPTURE.COMPLETED", "resource":{"custom_id":"' . $customId . '","id":"12A34567BC123456S","amount":{"currency_code":"USD","value":"30.00"}}}'
        );

        $this->client->request('POST', '/api/v1/payment/notify', server: [
            'headers' => [
                'Content-Type' => 'application/json'
            ],
        ], content: '{"event_type": "PAYMENT.CAPTURE.DENIED", "resource":{"custom_id":"' . $customId . '","id":"12A34567BC123456S","amount":{"currency_code":"USD","value":"30.00"}}}'
        );

        $response = $this->client->getResponse();
        $paymentEntity = $this->entityManager->getRepository(Payment::class)->findOneBy(['token' => $arrayResponse['token']]);
        
        $this->assertSame(200, $response->getStatusCode());
        $this->assertEquals(PaymentStatus::REFUNDED->value, $paymentEntity->getStatus()->value);
    }

    public function testCompleteThenRefundThenComplete(): void
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

        $customId = \base64_encode($arrayResponse['token'] . 'TR23FDF64564');
        $this->client->request('POST', '/api/v1/payment/notify', server: [
            'headers' => [
                'Content-Type' => 'application/json'
            ],
        ], content: '{"event_type": "PAYMENT.CAPTURE.COMPLETED", "resource":{"custom_id":"' . $customId . '","id":"12A34567BC123456S","amount":{"currency_code":"USD","value":"30.00"}}}'
        );

        $this->client->request('POST', '/api/v1/payment/notify', server: [
            'headers' => [
                'Content-Type' => 'application/json'
            ],
        ], content: '{"event_type": "PAYMENT.CAPTURE.DENIED", "resource":{"custom_id":"' . $customId . '","id":"12A34567BC123456S","amount":{"currency_code":"USD","value":"30.00"}}}'
        );

        $this->client->request('POST', '/api/v1/payment/notify', server: [
            'headers' => [
                'Content-Type' => 'application/json'
            ],
        ], content: '{"event_type": "PAYMENT.CAPTURE.COMPLETED", "resource":{"custom_id":"' . $customId . '","id":"12A34567BC123456S","amount":{"currency_code":"USD","value":"30.00"}}}'
        );

        $response = $this->client->getResponse();
        $paymentEntity = $this->entityManager->getRepository(Payment::class)->findOneBy(['token' => $arrayResponse['token']]);
        
        $this->assertSame(200, $response->getStatusCode());
        $this->assertEquals(PaymentStatus::CAPTURED->value, $paymentEntity->getStatus()->value);
    }
}