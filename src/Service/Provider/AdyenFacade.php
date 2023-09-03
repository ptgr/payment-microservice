<?php

namespace App\Service\Provider;

use App\Interface\IProviderStrategy;
use Symfony\Component\HttpFoundation\RedirectResponse;
use App\Entity\TokenItem;

class AdyenFacade implements IProviderStrategy
{
    public function process(TokenItem ...$tokenItems): RedirectResponse
    {
    }
}