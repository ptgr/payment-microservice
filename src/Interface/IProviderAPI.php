<?php

namespace App\Interface;

interface IProviderAPI
{
    public function getUrl(): string;
    public function getToken(): string;
}