<?php

namespace App\Interface;

use Symfony\Component\HttpFoundation\RedirectResponse;
use App\Entity\TokenItem;

interface IProviderStrategy
{
    public function process(TokenItem ...$tokenItems): RedirectResponse;
}