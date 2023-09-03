<?php

namespace App\Service\Provider\Paypal;

use App\Interface\IProviderUrl;
use App\Entity\TokenItem;
use PayPal\Api\Amount;
use PayPal\Api\Details;
use PayPal\Api\Item as PaypalItem;
use PayPal\Api\ItemList;
use PayPal\Api\Payer;
use PayPal\Api\Payment as PaypalPayment;
use PayPal\Api\RedirectUrls;
use PayPal\Api\Transaction;
use PayPal\Api\InputFields;
use PayPal\Api\WebProfile;
use PayPal\Auth\OAuthTokenCredential;
use PayPal\Rest\ApiContext;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class ProviderUrl implements IProviderUrl
{
    public function __construct(
        private UrlGeneratorInterface $router,
        private ParameterBagInterface $params
    ) {
    }

    public function get(TokenItem ...$tokenItems): string
    {
        $itemList = new ItemList();

        $subTotal = 0.00;
        $shippingTotal = 0.00;
        $taxTotal = 0.00;
        $externalItemIds = [];

        foreach ($tokenItems as $tokenItem) {

            $item = $tokenItem->getItem();

            $paypalItem = new PaypalItem();
            $paypalItem->setName($item->getName())
                ->setCurrency($item->getCurrencyCode())
                ->setQuantity($item->getQuantity())
                ->setPrice($item->getPrice());
            $itemList->addItem($paypalItem);

            $itemPrice = $item->getQuantity() * $item->getPrice();
            $subTotal += $itemPrice;

            $shippingTotal += $item->getShipping();
            $itemPrice += $item->getShipping();

            $externalItemIds[$item->getExternalId()] = true;

            if ($item->getDiscount() > 0) {
                $paypalItem = new PaypalItem();
                $price = $itemPrice * ($item->getDiscount() / 100);

                $paypalItem->setName("Discount")
                    ->setCurrency($item->getCurrencyCode())
                    ->setQuantity(1)
                    ->setPrice(-$price);
                $itemList->addItem($paypalItem);

                $subTotal -= $price;
                $itemPrice -= $price;
            }

            $taxTotal += $itemPrice * ($item->getVat() / 100);
        }

        $details = new Details();
        $details->setShipping($shippingTotal)
            ->setTax($taxTotal)
            ->setSubtotal($subTotal);

        $amount = new Amount();
        $amount->setCurrency($tokenItems[0]->getItem()->getCurrencyCode())
            ->setTotal($subTotal + $shippingTotal + $taxTotal)
            ->setDetails($details);

        $tokenId = $tokenItems[0]->getToken()->getId();
        $notifyURL = $this->params->get('app.domain') . $this->router->generate("notify", ['token' => $tokenId]);

        $paymentIdentification = \implode(", ", \array_keys($externalItemIds));

        $transaction = new Transaction();
        $transaction->setAmount($amount)
            ->setItemList($itemList)
            ->setCustom($paymentIdentification)
            ->setNotifyUrl($notifyURL)
            ->setInvoiceNumber($tokenId);

        $accountType = $this->params->get("app.paypal_sandbox") ? "sandbox" : $tokenItems[0]->getToken()->getAccountKey();

        $clientID = $this->params->get("app.paypal_account_$accountType");
        $clientSecret = $this->params->get(\sprintf("app.paypal_account_%s_secret", $accountType));

        $apiContext = new ApiContext(new OAuthTokenCredential($clientID, $clientSecret));
        $apiContext->setConfig(['mode' => $accountType === 'sandbox' ? 'sandbox' : 'live']);

        $inputFields = new InputFields();
        $inputFields->setNoShipping(1);

        $webProfile = new WebProfile();
        $webProfile->setName(uniqid())->setInputFields($inputFields);
        $webProfileId = $webProfile->create($apiContext)->getId();

        $returnURL = $this->params->get('app.domain') . $this->router->generate("capture", ['token' => $tokenId]);
        $cancelURL = $this->params->get('app.domain') . $this->router->generate("redirect", ['token' => $tokenId]);

        $redirectUrls = new RedirectUrls();
        $redirectUrls->setReturnUrl($returnURL)
            ->setCancelUrl($cancelURL);

        $paypalPayment = new PaypalPayment();
        $paypalPayment->setIntent("sale")
            ->setPayer((new Payer())->setPaymentMethod("paypal"))
            ->setRedirectUrls($redirectUrls)
            ->setTransactions([$transaction]);

        $paypalPayment->setExperienceProfileId($webProfileId);

        $paypalPayment->create($apiContext);
        return $paypalPayment->getApprovalLink();
    }
}