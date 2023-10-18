<?php

namespace App\Tests\Application ;

use App\Entity\Payment;
use App\Entity\Provider;
use App\Entity\Token;
use App\Enum\PaymentStatus;
use App\Enum\StatusType;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class StatusHistoryTest extends WebTestCase
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

    public function test404(): void
    {
        $this->client->request('GET', '/api/v1/payment/history/some_wrong_token');
        $response = $this->client->getResponse();

        $this->assertSame(404, $response->getStatusCode());
        $this->assertSame('application/json', $response->headers->get('Content-Type'));
    }

    public function testNoHistory(): void
    {
        $provider = $this->entityManager->getRepository(Provider::class)->findOneBy(['internal_key' => 'paypal']);

        $token = new Token;
        $token->setId('test_token');
        $token->setProvider($provider);
        $this->entityManager->persist($token);
        $this->entityManager->flush();

        $this->client->request('GET', '/api/v1/payment/history/test_token');
        $response = $this->client->getResponse();

        $this->assertSame(200, $response->getStatusCode());
        $this->assertEmpty(json_decode($response->getContent(), true));
    }

    public function testGetHistory(): void
    {
        $provider = $this->entityManager->getRepository(Provider::class)->findOneBy(['internal_key' => 'paypal']);

        $token = new Token;
        $token->setId('test_paid_token');
        $token->setProvider($provider);
        $this->entityManager->persist($token);

        $payment = new Payment;
        $payment->setToken($token);
        $payment->setStatus(PaymentStatus::CAPTURED);
        $payment->setAmount(100);
        $payment->setTransactionNumber('test_transaction_number');
        $this->entityManager->persist($payment);

        $this->entityManager->flush();

        // Test onFlush event
        $paymentEntity = $this->entityManager->getRepository(Payment::class)->find($payment->getId());
        $paymentEntity->setStatus(PaymentStatus::REFUNDED);
        $this->entityManager->flush();

        $this->client->request('GET', '/api/v1/payment/history/test_paid_token');
        $response = $this->client->getResponse();
        $arrayResponse = json_decode($response->getContent(), true);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertArrayHasKey('timestamp', $arrayResponse[0]);
        $this->assertEquals(StatusType::PAYMENT->value, $arrayResponse[0]['type']);
        $this->assertEquals(PaymentStatus::CAPTURED->value, $arrayResponse[0]['before']);
        $this->assertEquals(PaymentStatus::REFUNDED->value, $arrayResponse[0]['after']);
    }
}
