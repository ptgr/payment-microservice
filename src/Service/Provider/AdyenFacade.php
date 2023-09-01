<?php

namespace App\Service\Provider;

use App\Interface\IProviderStrategy;
use Symfony\Component\HttpFoundation\RedirectResponse;
use App\Entity\Token;

class AdyenFacade implements IProviderStrategy
{
    public function process(Token ...$tokens): RedirectResponse
    {
    }
}