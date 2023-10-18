<?php

namespace App\Tests\Application;

use App\Entity\Provider;
use App\Enum\TokenStatus;
use App\Entity\Token;
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

    public function testNoToken(): void
    {
        $this->client->request('GET', '/api/v1/payment/pay/fsdf');

        $response = $this->client->getResponse();
        $this->assertSame(302, $response->getStatusCode());
    }

    public function testNoActiveToken(): void
    {
        $provider = $this->entityManager->getRepository(Provider::class)->findOneBy(['internal_key' => 'paypal']);

        $token = new Token;
        $token->setId('test_pay_token');
        $token->setProvider($provider);
        $token->setStatus(TokenStatus::UNAVAILABLE);
        $this->entityManager->persist($token);
        
        $this->client->request('GET', '/api/v1/payment/pay/test_capture_token');

        $response = $this->client->getResponse();
        $this->assertSame(302, $response->getStatusCode());
    }
}