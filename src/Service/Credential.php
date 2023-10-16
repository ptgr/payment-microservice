<?php

namespace App\Service;

class Credential
{
    public function get(int $providerId, string $currencyCode): string
    {
        return "default";
    }
}