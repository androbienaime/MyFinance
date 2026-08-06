<?php

namespace App\Enums;

enum AccountHolderType: string
{
    case Personal = 'personal';
    case Merchant = 'merchant';
}