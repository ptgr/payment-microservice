<?php

namespace App\Service\Provider\Paypal;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use App\Interface\INotifiable;
use App\Enum\TokenStatus;
use Symfony\Component\HttpClient\HttpClient;
use App\Enum\PaymentStatus;
use App\Entity\Payment;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Doctrine\ORM\EntityManagerInterface;
use App\Interface\INotifyToken;
use App\Entity\Token;

class Notify implements INotifiable, INotifyToken
{
    private const VERIFY_URI = 'https://ipnpb.paypal.com/cgi-bin/webscr';
    private const SANDBOX_VERIFY_URI = 'https://ipnpb.sandbox.paypal.com/cgi-bin/webscr';

    private readonly Token $token;

    public function __construct(
        private EntityManagerInterface $entityManager,
        private ParameterBagInterface $params
    ) {
    }

    public function notify(Request $request): JsonResponse
    {
        if (!$this->isVerified($request->getContent()))
            throw new \RuntimeException("IPN verification failed " . $request->getContent());

        $notifyItem = $request->request->all();
        if (in_array(strtolower($notifyItem['payment_status']), ['reversed', 'refunded'])) {

            $paymentEntity = $this->entityManager->getRepository(Payment::class)->findOneBy(['transaction_number' => $notifyItem['txn_id']]);
            $paymentEntity->setStatus(PaymentStatus::REFUNDED);
            $this->entityManager->flush();
        }

        if (strtolower($notifyItem['payment_status']) === 'completed') {

            $paymentEntityCount = $this->entityManager->getRepository(Payment::class)->count(['token' => $this->token->getId()]);

            if ($paymentEntityCount === 0) {
                $paymentEntity = new Payment();
                $paymentEntity->setToken($this->token);
                $paymentEntity->setAmount($notifyItem['mc_gross']);
                $paymentEntity->setTransactionNumber($notifyItem['txn_id']);
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

    private function isVerified(string $rawBody): bool
    {
        $req = 'cmd=_notify-validate&' . $rawBody;
        $paypalUrl = $this->params->get("app.paypal_sandbox") ? self::SANDBOX_VERIFY_URI : self::VERIFY_URI;

        $response = HttpClient::create()->request(Request::METHOD_POST, $paypalUrl, [
            'headers' => [
                'User-Agent' => 'PHP-IPN-Verification-Script',
                'Connection' => 'Close',
                'Content-Type' => 'application/x-www-form-urlencoded'
            ],
            'body' => $req
        ]);

        $body = $response->getContent();
        return $body === 'VERIFIED';
    }
}