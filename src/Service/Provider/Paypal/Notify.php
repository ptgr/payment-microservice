<?php

namespace App\Service\Provider\Paypal;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use App\Interface\INotifiable;
use App\Enum\TokenStatus;
use App\Enum\PaymentStatus;
use App\Entity\Payment;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Doctrine\ORM\EntityManagerInterface;
use App\Interface\INotifyToken;
use App\Entity\Token;

class Notify implements INotifiable, INotifyToken
{

    private readonly Token $token;

    public function __construct(
        private EntityManagerInterface $entityManager,
        private ParameterBagInterface $params
    ) {
    }

    public function notify(Request $request): JsonResponse
    {
        $notifyItem = $request->toArray();
        if (strtoupper($notifyItem['event_type'] === 'PAYMENT.CAPTURE.DENIED')) {

            $paymentEntity = $this->entityManager->getRepository(Payment::class)->findOneBy(['transaction_number' => $notifyItem['resource']['id']]);
            $paymentEntity->setUpdatedAt();
            $paymentEntity->setStatus(PaymentStatus::REFUNDED);
            $this->entityManager->flush();
        }

        if (strtoupper($notifyItem['event_type'] === 'PAYMENT.CAPTURE.COMPLETED')) {

            $paymentEntityCount = $this->entityManager->getRepository(Payment::class)->count(['token' => $this->token->getId()]);

            if ($paymentEntityCount === 0) {
                $paymentEntity = new Payment(); 
                $paymentEntity->setToken($this->token);
                $paymentEntity->setAmount($notifyItem['resource']['amount']['value']);
                $paymentEntity->setTransactionNumber($notifyItem['resource']['id']);
                $paymentEntity->setStatus(PaymentStatus::CAPTURED);

                $this->entityManager->persist($paymentEntity);
                $this->entityManager->flush();
            }
        }

        $this->token->setStatus(TokenStatus::EXPIRED);
        $this->entityManager->persist($this->token);
        $this->entityManager->flush();

        return new JsonResponse();
    }

    public function setNotifyToken(Token $token): void
    {
        $this->token = $token;
    }
}