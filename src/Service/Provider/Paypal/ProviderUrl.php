<?php

namespace App\Service\Provider\Paypal;

use App\Entity\Item;
use App\Interface\IPaypalAPI;
use App\Interface\IProviderUrl;
use App\Entity\TokenItem;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class ProviderUrl implements IProviderUrl
{
    private float $subTotal = 0.00;
    private float $shippingTotal = 0.00;
    private float $taxTotal = 0.00;
    private float $discountPrice = 0.00;

    private array $itemList;
    private array $payload;

    public function __construct(
        private UrlGeneratorInterface $router,
        private ParameterBagInterface $params,
        private IPaypalAPI $api
    ) {
    }

    public function getPayload(): array
    {
        return $this->payload;
    }

    public function get(TokenItem ...$tokenItems): string
    {
        foreach ($tokenItems as $tokenItem) {

            $item = $tokenItem->getItem();
            $this->itemList[] = $this->addItemToList($item);
            $this->updateTotals($item);
        }

        $this->setPayload($tokenItems[0]);
        $this->api->setAccount($tokenItems[0]->getToken()->getAccountKey());

        $response = $this->api->createOrder($this->payload);

        foreach ($response['links'] ?? [] as $link) {
            if ($link['rel'] === 'payer-action')
                return $link['href'];
        }

        throw new \UnexpectedValueException(\sprintf('Paypal provider url did not return a payer action link - %s', $response));
    }

    private function addItemToList(Item $item): array
    {
        return [
            'name' => $item->getName() ?: '---',
            'quantity' => $item->getQuantity(),
            'unit_amount' => [
                "currency_code" => $item->getCurrencyCode(),
                "value" => $item->getPrice(),
            ]
        ];
    }

    private function updateTotals(Item $item): void
    {
        $itemPrice = $item->getQuantity() * $item->getPrice();
        $this->subTotal += $itemPrice;
        $this->shippingTotal += $item->getShipping();
        $itemPriceWithShipping = $itemPrice + $item->getShipping();
        $this->taxTotal += $itemPriceWithShipping * ($item->getVat() / 100);

        if ($item->getDiscount() > 0)
            $this->discountPrice += $itemPriceWithShipping * ($item->getDiscount() / 100);
    }

    private function setPayload(TokenItem $tokenItem): void
    {
        $tokenId = $tokenItem->getToken()->getId();
        $currencyCode = $tokenItem->getItem()->getCurrencyCode();

        $this->payload = [
            "intent" => "CAPTURE",
            "purchase_units" => [
                [
                    "custom_id" => \base64_encode($tokenId . $tokenItem->getTransactionName()),
                    "invoice_id" => $tokenItem->getTransactionName(),
                    'items' => $this->itemList,
                    "amount" => [
                        "currency_code" => $currencyCode,
                        "value" => (string) ($this->subTotal + $this->shippingTotal + $this->taxTotal - $this->discountPrice),
                        'breakdown' => [
                            'item_total' => [
                                "currency_code" => $currencyCode,
                                "value" => (string) $this->subTotal,
                            ],
                            'shipping' => [
                                "currency_code" => $currencyCode,
                                "value" => (string) $this->shippingTotal,
                            ],
                            'tax_total' => [
                                "currency_code" => $currencyCode,
                                "value" => (string) $this->taxTotal,
                            ],
                        ]
                    ]
                ]
            ],
            "payment_source" => [
                "paypal" => [
                    "experience_context" => [
                        "landing_page" => "LOGIN",
                        "user_action" => "PAY_NOW",
                        "return_url" => $this->params->get('app.domain') . $this->router->generate("capture", ['token' => $tokenId]),
                        "cancel_url" => $this->params->get('app.domain') . $this->router->generate("redirect", ['token' => $tokenId])
                    ]
                ]
            ]
        ];

        if ($this->discountPrice > 0) {
            $this->payload['purchase_units'][0]['amount']['breakdown']['discount'] = [
                "currency_code" => $currencyCode,
                "value" => (string) $this->discountPrice,
            ];
        }
    }
}