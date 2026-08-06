<?php

namespace App\Enums;

enum MerchantStatus: string
{
    case Pending = 'pending';   // demande soumise, en attente de validation
    case Active = 'active';
    case Suspended = 'suspended';
    case Rejected = 'rejected';
}