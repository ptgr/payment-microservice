<?php

namespace App\Interface;

interface IPaypalAPI
{
    public function setAccount(string $accountType): void;

    public function createOrder(array $payload): array;

    public function captureOrder(string $token): array;
}