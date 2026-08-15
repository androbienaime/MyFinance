<?php

namespace App\Http\Controllers\Api\Merchant;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function show(Request $request)
    {
        $merchant = $request->user('merchant');
        $account = $merchant->account;

        return response()->json([
            'business_name' => $merchant->business_name,
            'category' => $merchant->category,
            'business_registration_number' => $merchant->business_registration_number,
            'address' => $merchant->address,
            'account_code' => $account->code,
            'balance' => (float) $account->balance,
            'available_balance' => $account->availableBalance(),
            'transaction_fee_percentage' => $merchant->transactionFeePercentage(),
            'status' => $merchant->status->value,
        ]);
    }
}