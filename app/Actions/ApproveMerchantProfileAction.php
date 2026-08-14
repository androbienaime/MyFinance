<?php

// app/Actions/ApproveMerchantProfileAction.php
namespace App\Actions;

use App\Enums\MerchantStatus;
use App\Exceptions\TransactionRejectedException;
use App\Models\Core\MerchantProfile;
use App\Models\User;
use App\Notifications\MerchantApiCredentials;
use Illuminate\Support\Facades\Hash;

class ApproveMerchantProfileAction
{
    public function approve(MerchantProfile $profile, User $approver): MerchantProfile
    {
        if ($profile->status !== MerchantStatus::Pending) {
            throw new TransactionRejectedException('Ce profil marchand n\'est plus en attente de validation.');
        }

        $temporaryPassword = (string) random_int(100000000, 999999999);

        $profile->update([
            'status' => MerchantStatus::Active,
            'approved_by' => $approver->id,
            'approved_at' => now(),
            'api_password' => Hash::make($temporaryPassword),
            'api_password_changed_at' => now(),
        ]);

        $profile->account->customer?->notify(
            new MerchantApiCredentials($profile->account->code, $temporaryPassword)
        );

        if(env('APP_ENV') === 'local') {
            \Log::info("Temporary password generated for merchant profile: {$profile->id}  ");
        }

        return $profile;
    }

    public function reject(MerchantProfile $profile, User $approver, string $reason): MerchantProfile
    {
        if ($profile->status !== MerchantStatus::Pending) {
            throw new TransactionRejectedException('Ce profil marchand n\'est plus en attente de validation.');
        }

        $profile->update([
            'status' => MerchantStatus::Rejected,
            'approved_by' => $approver->id,
            'approved_at' => now(),
            'rejection_reason' => $reason,
        ]);

        return $profile;
    }

    public function suspend(MerchantProfile $profile, User $approver, string $reason): MerchantProfile
    {
        if ($profile->status !== MerchantStatus::Active) {
            throw new TransactionRejectedException('Seul un marchand actif peut etre suspendu.');
        }

        $profile->update([
            'status' => MerchantStatus::Suspended,
            'rejection_reason' => $reason,
        ]);

        // Coupe immediatement tout acces : session dashboard ET toutes
        // les cles API actives, sans exception.
        $profile->tokens()->delete();
        $profile->apiKeys()->where('is_active', true)->update([
            'is_active' => false,
            'revoked_at' => now(),
        ]);

        return $profile;
    }
}