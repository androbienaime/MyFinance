<?php

namespace App\Enums;

enum QrPaymentStatus: string
{
    case Pending = 'pending';
    case Completed = 'completed';
    case Expired = 'expired';
    case Cancelled = 'cancelled';
    case Failed = 'failed';
}