<?php

namespace App\Service;

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
            'adyen' => $this->container->get('adyen_facade'),
            default => throw new \InvalidArgumentException("There is no provider strategy for method_id " . $method->getId())
        };
    }

    public function exists(Method $method): bool
    {
        return \in_array($method->getInternalKey(), ['paypal', 'adyen']);
    }

    public function getAll(): array
    {
        return [
            $this->container->get('paypal_facade'),
            $this->container->get('adyen_facade')
        ];
    }
}