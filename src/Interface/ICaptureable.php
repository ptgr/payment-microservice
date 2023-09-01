<?php

namespace App\Interface;

use Symfony\Component\HttpFoundation\RedirectResponse;
use App\Entity\Token;

interface ICaptureable
{
    public function capture(Token $token, array $data): bool|RedirectResponse;
}