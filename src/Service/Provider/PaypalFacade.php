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

    public function notify(Request $request, ?Token $token): JsonResponse
    {
        return $this->notifyInstance->notify($request, $token);
    }

    public function isProviderNotification(Request $request, ?string $token): bool
    {
        $data = empty($request->getContent()) ? [] : $request->toArray();
        if (!isset($data['resource']['custom_id']))
            return false;

        $customIdDecoded = \base64_decode($data['resource']['custom_id']);
        $tokenId = \substr($customIdDecoded, 0, 41);
        $transactionName = \substr($customIdDecoded, 41);

        $tokenItem = $this->entityManager->getRepository(TokenItem::class)->findOneBy(['token' => $tokenId, 'transactionName' => $transactionName]);
        if ($tokenItem === null)
            return false;

        if ($this->notifyInstance instanceof INotifyToken)
            $this->notifyInstance->setNotifyToken($tokenItem->getToken());

        return $tokenItem->getToken()->getProvider()->getInternalKey() === 'paypal';
    }
}