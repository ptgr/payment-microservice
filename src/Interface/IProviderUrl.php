<?php

namespace App\Interface;

use App\Entity\Token;

interface IProviderUrl
{
    public function get(Token ...$tokens): string;
}