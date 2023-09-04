<?php

namespace App\Service\Provider\Adyen;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use App\Interface\INotifiable;
use App\Enum\PaymentStatus;
use App\Entity\Payment;
use Adyen\Util\HmacSignature;
use App\Entity\Token;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Doctrine\ORM\EntityManagerInterface;

class Notify implements INotifiable
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ParameterBagInterface $params
    ) {
    }

    private readonly Token $token;

    public function notify(Request $request): JsonResponse
    {
        $data = $request->toArray();

        $notifyItem = reset($data["notificationItems"]);
        $notifyItem = $notifyItem['NotificationRequestItem'];

        $hmacKey = $this->params->get("eshop.adyen_accounts.{$this->token->getAccountKey()}.hmac_key");
        $valid = (new HmacSignature())->isValidNotificationHMAC($hmacKey, $notifyItem);

        if (!$valid || $notifyItem['success'] == "false")
            return new JsonResponse(['notificationResponse' => "[accepted]"]);

        if (in_array($notifyItem['eventCode'], ['CANCELLATION', 'CANCEL_OR_REFUND', 'REFUND'])) {

            $paymentEntity = $this->entityManager->getRepository(Payment::class)->findOneBy(['transaction_number' => $notifyItem['originalReference']]);
            $paymentEntity->setStatus(PaymentStatus::REFUNDED);
            $this->entityManager->flush();
        }

        if (!in_array($notifyItem['eventCode'], ['AUTHORISATION', 'CAPTURE']))
            return new JsonResponse(['notificationResponse' => "[accepted]"]);

        $paymentEntityCount = $this->entityManager->getRepository(Payment::class)->count(['token' => $this->token->getId()]);
        if ($paymentEntityCount === 0) {

            $paymentEntity = new Payment();
            $paymentEntity->setToken($this->token);
            $paymentEntity->setAmount($notifyItem['amount']['value'] / 100);
            $paymentEntity->setTransactionNumber($notifyItem['pspReference']);
            $paymentEntity->setStatus(PaymentStatus::CAPTURED);

            $this->entityManager->persist($paymentEntity);
            $this->entityManager->flush();
        }

        return new JsonResponse(['notificationResponse' => "[accepted]"]);

    }

    public function setNotifyToken(Token $token): void
    {
        $this->token = $token;
    }
}