<?php

namespace App\Service\Provider\Paypal;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use App\Interface\INotifiable;
use App\Enum\TokenStatus;
use App\Entity\Payment;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Doctrine\ORM\EntityManagerInterface;
use App\Interface\INotifyToken;
use App\Entity\Token;

class Notify implements INotifiable, INotifyToken
{
    private readonly Token $token;

    private const REFUND_EVENT_TYPES = ['PAYMENT.CAPTURE.DENIED'];
    private const COMPLETE_EVENT_TYPES = ['PAYMENT.CAPTURE.COMPLETED'];

    public function __construct(
        private EntityManagerInterface $entityManager,
        private ParameterBagInterface $params
    ) {
    }

    public function setNotifyToken(Token $token): void
    {
        $this->token = $token;
    }

    public function notify(Request $request, ?Token $token): JsonResponse
    {
        if ($token !== null)
            $this->token = $token;

        $notifyItem = $request->toArray();
        $eventType = \strtoupper($notifyItem['event_type']);
        $paymentRepository = $this->entityManager->getRepository(Payment::class);

        if (\in_array($eventType, self::REFUND_EVENT_TYPES) && !empty($notifyItem['resource']['id']))
            $paymentRepository->setAsRefund($notifyItem['resource']['id']);

        if (\in_array($eventType, self::COMPLETE_EVENT_TYPES) && !empty($notifyItem['resource']['id']) && !empty($notifyItem['resource']['amount']['value']))
            $paymentRepository->store($this->token, $notifyItem['resource']['amount']['value'], $notifyItem['resource']['id']);

        $this->token->setStatus(TokenStatus::EXPIRED);
        $this->entityManager->persist($this->token);
        $this->entityManager->flush();

        return new JsonResponse();
    }
}