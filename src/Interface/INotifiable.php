<?php

namespace App\Interface;

use App\Entity\Token;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;

interface INotifiable
{
    public function notify(Request $request, ?Token $token): JsonResponse;
}