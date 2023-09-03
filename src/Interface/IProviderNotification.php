<?php

namespace App\Interface;

use Symfony\Component\HttpFoundation\Request;

interface IProviderNotification
{
    public function isProviderNotification(Request $request, ?string $token): bool;
}