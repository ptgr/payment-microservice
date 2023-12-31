<?php

namespace App\Tests\Application ;

use App\Entity\Payment;
use App\Entity\Provider;
use App\Entity\Token;
use App\Enum\PaymentStatus;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class IsPaidTest extends WebTestCase
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
        $this->client->request('GET', '/api/v1/payment/is-paid/some_wrong_token');
        $response = $this->client->getResponse();

        $this->assertSame(404, $response->getStatusCode());
        $this->assertSame('application/json', $response->headers->get('Content-Type'));
    }

    public function testTokenIsNotPaid(): void
    {
        $provider = $this->entityManager->getRepository(Provider::class)->findOneBy(['internal_key' => 'paypal']);

        $token = new Token;
        $token->setId('test_token');
        $token->setProvider($provider);
        $this->entityManager->persist($token);
        $this->entityManager->flush();

        $this->client->request('GET', '/api/v1/payment/is-paid/test_token');
        $response = $this->client->getResponse();

        $this->assertSame(200, $response->getStatusCode());
        $this->assertFalse(json_decode($response->getContent(), true)['paid']);
    }

    public function testTokenIsPaid(): void
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

        $this->client->request('GET', '/api/v1/payment/is-paid/test_paid_token');
        $response = $this->client->getResponse();
        $arrayResponse = json_decode($response->getContent(), true);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertTrue($arrayResponse['paid']);
        $this->assertArrayHasKey('paid_created_at', $arrayResponse);
        $this->assertArrayHasKey('status_updated_at', $arrayResponse);
        $this->assertEquals(PaymentStatus::CAPTURED->value, $arrayResponse['status']);
    }
}
