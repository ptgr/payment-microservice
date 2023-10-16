<?php

namespace App\Service;

use App\Interface\INotifiable;
use App\Interface\IProviderNotification;
use App\Interface\IProviderStrategy;
use Symfony\Component\DependencyInjection\ContainerInterface;
use App\Entity\Provider;
use Doctrine\ORM\EntityManagerInterface;

class ProviderStrategy
{
    private array $allProviders;

    public function __construct(
        private EntityManagerInterface $entityManager,
        private ContainerInterface $container
    ) {
        $this->allProviders = $entityManager->getRepository(Provider::class)->findAll();
    }

    public function resolve(Provider $selectedProvider): IProviderStrategy
    {
        foreach ($this->allProviders as $provider) {
            if ($provider->getInternalKey() === $selectedProvider->getInternalKey())
                return $this->getContainer($provider);
        }

        throw new \InvalidArgumentException("There is no provider strategy for provider_id " . $selectedProvider->getId());
    }

    public function getNotificationProviders(): \Generator
    {
        foreach ($this->allProviders as $provider) {
            $providerInstance = $this->getContainer($provider);

            if (!$providerInstance instanceof IProviderNotification || !$providerInstance instanceof INotifiable)
                continue;

            return yield $providerInstance;
        }
    }

    private function getContainer(Provider $provider): object|null
    {
        $containerName = \sprintf('%s_facade', $provider->getInternalKey());
        return $this->container->get($containerName);
    }
}