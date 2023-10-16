<?php

namespace App\Service;

use App\Interface\INotifiable;
use App\Interface\IProviderNotification;
use App\Interface\IProviderStrategy;
use Symfony\Component\DependencyInjection\ContainerInterface;
use App\Entity\Method;
use Doctrine\ORM\EntityManagerInterface;

class ProviderStrategy
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ContainerInterface $container
    )
    {
    }

    public function resolve(Method $method): IProviderStrategy
    {
        return match ($method->getInternalKey()) {
            'paypal' => $this->container->get('paypal_facade'),
            default => throw new \InvalidArgumentException("There is no provider strategy for method_id " . $method->getId())
        };
    }

    public function exists(Method $method): bool
    {
        return \in_array($method->getInternalKey(), ['paypal']);
    }

    public function getNotificationProviders(): \Generator
    {
        $providers = ['paypal_facade'];

        foreach ($providers as $provider) {
            $providerInstance = $this->container->get($provider);

            if (!$providerInstance instanceof IProviderNotification || !$providerInstance instanceof INotifiable)
                continue;

            return yield $providerInstance;
        }
    }
}