<?php

namespace App\Enum;

enum PaymentStatus: string 
{
    case AUTHORIZED = 'authorized';
    case CAPTURED = 'captured';
    case REFUNDED = 'refunded';
}