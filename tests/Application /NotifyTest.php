<?php

namespace App\Tests\Application;

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

    public function testNoProviderValidGET(): void
    {
        $this->client->request('GET', '/api/v1/payment/notify');

        $response = $this->client->getResponse();
        $this->assertSame(422, $response->getStatusCode());
    }

    public function testNoProviderValidPOST(): void
    {
        $this->client->request('POST', '/api/v1/payment/notify');

        $response = $this->client->getResponse();
        $this->assertSame(422, $response->getStatusCode());
    }
}