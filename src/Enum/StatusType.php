<?php

namespace App\Enum;

enum StatusType: string
{
    case PAYMENT = 'payment';
    case TOKEN = 'token';
}