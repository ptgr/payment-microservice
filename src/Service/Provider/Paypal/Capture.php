<?php

namespace App\Service\Provider\Paypal;

use App\Entity\Token;
use App\Interface\ICaptureable;
use App\Enum\TokenStatus;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use PayPal\Auth\OAuthTokenCredential;
use PayPal\Rest\ApiContext;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use PayPal\Api\PaymentExecution;
use PayPal\Api\Payment;

class Capture implements ICaptureable
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ParameterBagInterface $params
    ) {
    }

    public function capture(Token $token, array $data): bool|RedirectResponse
    {
        $accountType = $this->params->get("app.paypal_sandbox") ? "sandbox" : $token->getAccountKey();

        $clientID = $this->params->get("app.paypal_account_$accountType");
        $clientSecret = $this->params->get(\sprintf("app.paypal_account_%s_secret", $accountType));

        $apiContext = new ApiContext(new OAuthTokenCredential($clientID, $clientSecret));
        $apiContext->setConfig(['mode' => $accountType === 'sandbox' ? 'sandbox' : 'live']);
        $payment = Payment::get($data['paymentId'], $apiContext);

        $execution = new PaymentExecution();
        $execution->setPayerId($data['PayerID']);

        $payment->execute($execution, $apiContext);

        $tokenEntities = $this->entityManager->getRepository(Token::class)->findBy(['token' => $token->getToken()]);
        foreach ($tokenEntities as $tokenEntity)
            $tokenEntity->setStatus(TokenStatus::UNAVAILABLE);
        
        $this->entityManager->flush();
        return true;
    }
}