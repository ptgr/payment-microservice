<?php

namespace App\Service\Provider\Adyen;

use App\Interface\IProviderUrl;
use Adyen\Service\Checkout;
use Adyen\Environment;
use Adyen\Client;
use App\Entity\TokenItem;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class ProviderUrl implements IProviderUrl
{
    public function __construct(
        private ParameterBagInterface $params
    ) {
    }

    public function get(TokenItem ...$tokenItems): string
    {
        $accountType = $this->params->get("app.adyen_sandbox") ? "sandbox" : $tokenItems[0]->getToken()->getAccountKey();

        $client = new Client();
        $client->setApplicationName("Adyen Webshop");
        $client->setXApiKey($this->params->get("eshop.adyen_accounts.$accountType.api_key"));

        if ($accountType === "sandbox")
            $client->setEnvironment(Environment::TEST);
        else
            $client->setEnvironment(Environment::LIVE, $this->params->get("eshop.adyen_accounts.$accountType.endfix"));
        
        $total = 0.00;
        $externalItemIds = [];

        foreach ($tokenItems as $tokenItem) {

            $item = $tokenItem->getItem();
            $externalItemIds[$item->getExternalId()] = true;
            $total += $item->getTotalPrice();
        }

        $data = [
            'reference' => \implode(", ", \array_keys($externalItemIds)),
            'shopperReference' => $tokenItems[0]->getToken()->getId(),
            'amount' => [
                'value' => (string) ($total * 100),
                'currency' => $tokenItems[0]->getItem()->getCurrencyCode()
            ],
            'merchantAccount' => $this->params->get("eshop.adyen_accounts.$accountType.account_id"),
            'returnUrl' => $this->params->get('app.redirect_after_success_payment'),
            'reusable' => true,
            'allowedPaymentMethods' => ['scheme']
        ];

        $response = (new Checkout($client))->paymentLinks($data);
        return $response['url'];
    }
}