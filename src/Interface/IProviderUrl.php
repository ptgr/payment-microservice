<?php

namespace App\Interface;

use App\Entity\TokenItem;

interface IProviderUrl
{
    public function get(TokenItem ...$tokenItems): string;
}