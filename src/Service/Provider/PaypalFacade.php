<?php

namespace App\Service\Provider;

use App\Interface\IProviderStrategy;
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

    public function process(Token ...$tokens): RedirectResponse
    {
        $providerUrl = $this->providerUrlInstance->get(...$tokens);
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

    public function isProviderNotification(Request $request): bool
    {
        $token = $request->query->get('token');
        if ($token === null)
            return false;

        $tokenEntity = $this->entityManager->getRepository(Token::class)->findOneBy(['token' => $token]);
        if ($tokenEntity === null)
            return false;
        
        if ($this->notifyInstance instanceof INotifyToken)
            $this->notifyInstance->setNotifyToken($tokenEntity);
        
        return $tokenEntity->getMethod()->getInternalKey() === 'paypal';
    }
}