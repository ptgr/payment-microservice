<?php

namespace App\Service\Provider;

use App\Interface\IProviderStrategy;
use App\Entity\Token;
use Doctrine\ORM\EntityManagerInterface;
use App\Interface\INotifiable;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use App\Interface\IProviderUrl;
use App\Interface\IProviderNotification;
use Symfony\Component\HttpFoundation\RedirectResponse;
use App\Entity\TokenItem;

class AdyenFacade implements IProviderStrategy, INotifiable, IProviderNotification
{
    public function __construct(
        private IProviderUrl $providerUrlInstance,
        private INotifiable $notifyInstance,
        private ParameterBagInterface $params,
        private EntityManagerInterface $entityManager
    ) {
    }

    public function process(TokenItem ...$tokenItems): RedirectResponse
    {
        $providerUrl = $this->providerUrlInstance->get(...$tokenItems);
        return new RedirectResponse($providerUrl);
    }

    public function notify(Request $request): JsonResponse
    {
        return $this->notifyInstance->notify($request);
    }

    public function isProviderNotification(Request $request, ?string $token): bool
    {
        $data = $request->toArray();
        if (!isset($data['notificationItems']))
            return false;
            
        $notifyItem = reset($data["notificationItems"]);
        $notifyItem = $notifyItem['NotificationRequestItem'];

        $tokenEntity = $this->entityManager->getRepository(Token::class)->find($notifyItem['additionalData']['shopperReference'] ?? "");
        if ($tokenEntity === null)
            return false;
        
        if ($this->notifyInstance instanceof INotifyToken)
            $this->notifyInstance->setNotifyToken($tokenEntity);
        
        return $tokenEntity->getMethod()->getInternalKey() === 'adyen';
    }
}