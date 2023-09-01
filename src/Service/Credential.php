<?php

namespace App\Service;

class Credential
{
    public function get(int $methodId, string $currencyCode): string
    {
        return "default";
    }
}