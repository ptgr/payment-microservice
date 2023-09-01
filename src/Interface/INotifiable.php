<?php

namespace App\Interface;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;

interface INotifiable
{
    public function notify(Request $request): JsonResponse;
}