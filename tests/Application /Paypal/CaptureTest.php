<?php

namespace App\Tests\Application\Paypal;

use App\Entity\Provider;
use App\Enum\TokenStatus;
use App\Entity\Token;
use App\Interface\IPaypalAPI;
use App\Service\Provider\Paypal\Api;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class CaptureTest extends WebTestCase
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

    public function testSuccessCapture(): void
    {
        $provider = $this->entityManager->getRepository(Provider::class)->findOneBy(['internal_key' => 'paypal']);

        $token = new Token;
        $token->setId('test_capture_token');
        $token->setProvider($provider);
        $this->entityManager->persist($token);

        $paypalApiMock = $this->createMock(IPaypalAPI::class);
        $paypalApiMock->expects($this->once())->method('captureOrder')->willReturn([]);
        self::$kernel->getContainer()->set(Api::class, $paypalApiMock);
        
        $this->client->request('GET', '/api/v1/payment/capture/test_capture_token?token=paypal_token');

        $response = $this->client->getResponse();
        $token = $this->entityManager->getRepository(Token::class)->find($token->getId());

        $this->assertEquals(TokenStatus::UNAVAILABLE->value, $token->getStatus()->value);
        $this->assertSame(302, $response->getStatusCode());
    }
}