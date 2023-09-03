<?php

namespace App\Service\Provider;

use App\Interface\IProviderStrategy;
use App\Entity\TokenItem;
use App\Interface\INotifyToken;
use Doctrine\ORM\EntityManagerInterface;
use App\Interface\IProviderNotification;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use App\Interface\INotifiable;
use App\Interface\IProviderUrl;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use App\Interface\ICaptureable;
use Symfony\Component\HttpFoundation\RedirectResponse;
use App\Entity\Token;

class PaypalFacade implements IProviderStrategy, ICaptureable, INotifiable, IProviderNotification
{
    public function __construct(
        private IProviderUrl $providerUrlInstance,
        private ICaptureable $captureInstance,
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

    public function capture(Token $token, array $data): bool|RedirectResponse
    {
        $actionSuccess = $this->captureInstance->capture($token, $data);
        if ($actionSuccess)
            return new RedirectResponse($this->params->get('app.redirect_after_success_payment'));
        
        return new RedirectResponse($this->params->get('app.redirect_after_failure_payment'));
    }

    public function notify(Request $request): JsonResponse
    {
        return $this->notifyInstance->notify($request);
    }

    public function isProviderNotification(Request $request, ?string $token): bool
    {
        if ($token === null)
            return false;

        $tokenEntity = $this->entityManager->getRepository(Token::class)->find($token);
        if ($tokenEntity === null)
            return false;
        
        if ($this->notifyInstance instanceof INotifyToken)
            $this->notifyInstance->setNotifyToken($tokenEntity);
        
        return $tokenEntity->getMethod()->getInternalKey() === 'paypal';
    }
}