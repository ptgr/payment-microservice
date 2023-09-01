<?php

namespace App\Enum;

enum TokenStatus: string
{
    case ACTIVE = 'active';
    case UNAVAILABLE = 'unavailable';
    case EXPIRED = 'expired';
}