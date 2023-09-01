<?php

namespace App\Interface;

use App\Entity\Token;

interface INotifyToken
{
    public function setNotifyToken(Token $token): void;
}