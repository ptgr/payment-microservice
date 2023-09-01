<?php

namespace App\Interface;

use Symfony\Component\HttpFoundation\RedirectResponse;
use App\Entity\Token;

interface IProviderStrategy
{
    public function process(Token ...$tokens): RedirectResponse;
}