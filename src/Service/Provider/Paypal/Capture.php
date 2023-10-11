<?php

namespace App\Service\Provider\Paypal;

use App\Entity\Token;
use App\Interface\ICaptureable;
use App\Enum\TokenStatus;
use App\Interface\IPaypalAPI;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class Capture implements ICaptureable
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ParameterBagInterface $params,
        private IPaypalAPI $api
    ) {
    }

    public function capture(Token $token, array $data): bool|RedirectResponse
    {
        $this->api->setAccount($token->getAccountKey());
        $this->api->captureOrder($data['token']);

        $token->setStatus(TokenStatus::UNAVAILABLE);
        $this->entityManager->flush();

        return true;
    }
}